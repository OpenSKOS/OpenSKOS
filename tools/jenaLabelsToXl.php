<?php

/**
 * OpenSKOS
 *
 * LICENSE
 *
 * This source file is subject to the GPLv3 license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @category   OpenSKOS
 * @package    OpenSKOS
 * @copyright  Copyright (c) 2015 Picturae (http://www.picturae.com)
 * @author     Picturae
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 */

use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Namespaces\SkosXl;
use OpenSkos2\Concept;
use OpenSkos2\Rdf\Uri;
use OpenSkos2\Rdf\Literal;
use OpenSkos2\SkosXl\LabelManager;
use OpenSkos2\SkosXl\Label;
use OpenSkos2\SkosXl\LabelCollection;

/**
 * Alternate version of the labelToXl script. This one only uses Jena and not SOLR
 */
require dirname(__FILE__) . '/autoload.inc.php';

//How often to commit rows
define ('COMMIT_FREQUENCY', 1);

$opts = [
    'env|e=s' => 'The environment to use (defaults to "production")',
    'debug' => 'Show debug info.',
    'modified|m=s' => 'Process only those modified after that date.',
    'skipDone|s' => 'Skip concepts which are done.',
];

try {
    $OPTS = new Zend_Console_Getopt($opts);
} catch (Zend_Console_Getopt_Exception $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    echo str_replace('[ options ]', '[ options ] action', $OPTS->getUsageMessage());
    exit(1);
}

require dirname(__FILE__) . '/bootstrap.inc.php';

/* @var $diContainer DI\Container */
$diContainer = Zend_Controller_Front::getInstance()->getDispatcher()->getContainer();

$logger = new \Monolog\Logger("Logger");
$logLevel = \Monolog\Logger::INFO;
if ($OPTS->getOption('debug')) {
    $logLevel = \Monolog\Logger::DEBUG;
}
$logger->pushHandler(new \Monolog\Handler\ErrorLogHandler(
    \Monolog\Handler\ErrorLogHandler::OPERATING_SYSTEM,
    $logLevel
));

processNonXLConcepts();

?>
<?php

/**
 *
 * Walk through all concepts without xlLabels, and append
 *
 */
function processNonXLConcepts()
{
    global $diContainer, $logger, $OPTS;

    $limit = COMMIT_FREQUENCY;

    /* @var $resourceManager \OpenSkos2\Rdf\ResourceManagerWithSearch */
    $resourceManager = $diContainer->make('\OpenSkos2\Rdf\ResourceManagerWithSearch');

    /*
     * Query anything with Skos:: label but no SkosXL Preflabel
     */
    $labelsList = getLabelsMap();
    foreach($labelsList as $xlLabel => $skosLabel){

        $logger->info(sprintf("Convert %s to  %s", $xlLabel, $skosLabel));

        $totalItems = countItemsWithoutLabels($resourceManager, $skosLabel, $xlLabel);
        $logger->info(sprintf("Total concepts to process: %s", $totalItems));


        $outerCounter = 0;
        do {

            //Fetch all subjects missing this label
            $sparql = getQueryItemsWithoutLabels($skosLabel, $xlLabel, COMMIT_FREQUENCY);
            $unLabeled = $resourceManager->query($sparql);

            $toProcess = count($unLabeled);
            if($toProcess == 0){
                //This is the only exit point of the loop. Please don't delete it without replacing it
                break;
            }

            $insertResources = new \OpenSkos2\Rdf\ResourceCollection([]);
            $innerCounter = 0;

            //Loop anything missing an XL Label
            foreach ($unLabeled as $row) {
                $innerCounter++;
                $outerCounter++;
                print var_export(($row));
                print "\n\n";

                $subjectUri = $row->subject->getUri();
                $labelContent = $row->coreLabel->getValue();

                $jenaObject = $resourceManager->fetchByUri($subjectUri);

                // Create concept only with xl labels to insert it as partial resource
                $partialConcept = new \OpenSkos2\Concept($jenaObject->getUri());


                //Get every instance of the simple label attached to this property
                //$allSimpleLabels = $jenaObject->getProperty($skosLabel);
                $valueAsOpenSkosObject = new Literal($row->coreLabel);
                $valueAsOpenSkosObject->setLanguage($row->coreLabel->getLang());
                print var_export($valueAsOpenSkosObject);

                $newLabel = new Label(Label::generateUri());
                $newLabel->setProperty(SkosXl::LITERALFORM, $valueAsOpenSkosObject);
                $newLabel->ensureMetadata();

                $partialConcept->setProperty($xlLabel, $newLabel);

                $insertResources->append($partialConcept);

            }
            try {
                //Commit the resources we just processed
                $logger->info(sprintf("Commit after %d of %s concepts", $outerCounter, $totalItems));
                $resourceManager->extendCollection($insertResources);
            } catch (\Exception $ex) {
                $logger->warning(
                    'Problem with the labels for "' . $subjectUri
                    . '". The message is: ' . $ex->getMessage()
                );
            }
            die("<hr>\n" . __FILE__ . " " . __LINE__ . "\n Marker <hr>");   //FIND_ME_AGAIN
            gc_collect_cycles();
        }while (true);
    }
}



/**
 * Query to retrieve concept with a prefLabel and no prefXL label
 * @param $limit  Limit results to this value if set
 * @return string
 */
function getQueryItemsWithoutLabels($skosLabel, $xlLabel, $limit = null)
{

    $queryOut =  <<<MY_SPARQL
  SELECT ?subject ?coreLabel ?status
WHERE { 
  ?subject <%s> <%s> .
  ?subject <%s> ?coreLabel.
  ?subject <%s> ?status.
  OPTIONAL { ?subject <%s> ?object } .
  FILTER ( !bound(?object) )  
}
%s

MY_SPARQL;

    $queryOut = sprintf(    $queryOut,
        \OpenSkos2\Namespaces\Rdf::TYPE,
        'http://www.w3.org/2004/02/skos/core#Concept',
        $skosLabel,
        OpenSkos::STATUS,
        $xlLabel,
        ($limit ? "LIMIT $limit" : '')
    );
    return $queryOut;
}


/**
 * Query to retrieve concept with a prefLabel and no prefXL label
 * @param $limit  Limit results to this value if set
 * @return string
 */
function countItemsWithoutLabels($resourceManager, $skosLabel, $xlLabel)
{

    $query =  <<<MY_SPARQL
SELECT (COUNT(?subject) as ?count)	
WHERE { 
  ?subject <%s> <%s> .
  ?subject <%s> ?coreLabel.
  ?subject <%s> ?status.
  OPTIONAL { ?subject <%s> ?object } .
  FILTER ( !bound(?object) )  
}
MY_SPARQL;

    $query = sprintf(    $query,
        \OpenSkos2\Namespaces\Rdf::TYPE,
        'http://www.w3.org/2004/02/skos/core#Concept',
        $skosLabel,
        OpenSkos::STATUS,
        $xlLabel
    );

    $result = $resourceManager->query($query);

    $count = $result[0]->count->getValue();

    return ((int)$count);
}


function getLabelsMap(){
    return array(
        SkosXl::PREFLABEL => Skos::PREFLABEL,
        SkosXl::ALTLABEL => Skos::ALTLABEL,
        SkosXl::HIDDENLABEL => Skos::HIDDENLABEL,
    );

}
