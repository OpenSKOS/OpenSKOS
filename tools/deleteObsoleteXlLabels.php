<?php

/*
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
 *
 * Delete all xl labels older than 1 week that are currently
 * not attached to any concept.
 */

require dirname(__FILE__) . '/autoload.inc.php';

$options = [
    'env|e=s'   => 'The environment to use (defaults to "production")',
    'days|d=i'    => 'Labels modified in less that this mount will not be deleted.'
                 . 'Default is 7 days.',
    'verbose|v' => 'Verbose',
    'help|h'    => 'Show this help',
];

try {
    $OPTS = new Zend_Console_Getopt($options);
} catch (Zend_Console_Getopt_Exception $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    echo str_replace('[ options ]', '[ options ] action', $OPTS->getUsageMessage());
    exit(1);
}

require dirname(__FILE__) . '/bootstrap.inc.php';

if ($OPTS->getOption('help')) {
    exit($OPTS->getUsageMessage());
}

/* @var $diContainer DI\Container */
$diContainer = Zend_Controller_Front::getInstance()->getDispatcher()->getContainer();

$logger = new \Monolog\Logger("Logger");
$logLevel = \Monolog\Logger::INFO;
if ($OPTS->getOption('verbose')) {
    $logLevel = \Monolog\Logger::DEBUG;
}
$logger->pushHandler(new \Monolog\Handler\ErrorLogHandler(
    \Monolog\Handler\ErrorLogHandler::OPERATING_SYSTEM,
    $logLevel
));

if ($OPTS->getOption('days')) {
    $maxLabelAgeInDays = (int)$OPTS->getOption('days');
} else {
    $maxLabelAgeInDays = 7;
}

/* @var $resourceManager \OpenSkos2\Rdf\ResourceManagerWithSearch */
$resourceManager = $diContainer->make('\OpenSkos2\Rdf\ResourceManagerWithSearch');

/* @var $solrResourceManager \OpenSkos2\Solr\ResourceManager */
$solrResourceManager = $diContainer->make('\OpenSkos2\Solr\ResourceManager');

//We will delete only labels that are not linked to a concept
$sparqlWhere = '
    ?label <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://www.w3.org/2008/05/skos-xl#Label> 
    NOT EXISTS {
        [<http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://www.w3.org/2004/02/skos/core#Concept>] ?predicate ?label
    }
    OPTIONAL {
        ?label <http://purl.org/dc/terms/modified> ?modifiedTime
    }
';

$total = getTotal($resourceManager, $sparqlWhere);
$rows = 100;

$fetchLabels = "
    DESCRIBE ?label
    WHERE {
        $sparqlWhere
    }
    LIMIT $rows
";

//@TODO: Think of a way to globally define this list so in future when new 
//default params are added to the label this script will continue to work
$defaultXlLabelParameters = [
    \OpenSkos2\Namespaces\Rdf::TYPE,
    \OpenSkos2\Namespaces\OpenSkos::TENANT,
    \OpenSkos2\Namespaces\DcTerms::MODIFIED,
    \OpenSkos2\Namespaces\SkosXl::LITERALFORM
];

$labelsToDelete = [];

$offset = 0;
while ($offset < $total) {

    $labels = $resourceManager->fetchQuery($fetchLabels . ' OFFSET ' . $offset);
    
    $offset = $offset + $rows;
    
    foreach ($labels as $label) {
        /* @var $label \OpenSkos2\SkosXl\Label */
        $logger->debug($label->getUri());

        try {
            $labelShouldBeDeleted = true;
            
            //We will not delete labels modified in less than 1 week
            $modifiedParams = $label->getProperty(OpenSkos2\Namespaces\DcTerms::MODIFIED);
            if (!empty($modifiedParams) && $modifiedParams[0] instanceof OpenSkos2\Rdf\Literal) {
                /* @var $date DateTime */
                $dateModified = $modifiedParams[0]->getValue();
                $now = new DateTime(date('c'));
                if ($dateModified->diff($now)->days < $maxLabelAgeInDays) {
                    $labelShouldBeDeleted = false;
                }
            }
            
            foreach ($label->getProperties() as $key => $value) {
                //We will not delete labels that contain extra information
                if (!in_array($key, $defaultXlLabelParameters)) {
                    $labelShouldBeDeleted = false;
                }
            }

            if ($labelShouldBeDeleted) {
                $labelsToDelete[] = $label;
            }
        } catch (\Exception $exc) {
            echo $exc->getMessage() . PHP_EOL;
            echo $exc->getTraceAsString() . PHP_EOL;
        }
    }
}

foreach ($labelsToDelete as $label) {
    //Delete from Jena
    $resourceManager->delete($label);
    
    //Delete from Solr
    $solrResourceManager->delete($label);
}

if (count($labelsToDelete > 0)) {
    $solrResourceManager->commit();
}

$logger->info('Labels not linked to concepts: ' . $total);
$logger->info('Removed labels: ' . count($labelsToDelete));
$logger->info("Done!");

/**
 * Get total amount of concepts
 * @param \OpenSkos2\Rdf\ResourceManagerWithSearch $resourceManager
 * @return int
 */
function getTotal(\OpenSkos2\Rdf\ResourceManagerWithSearch $resourceManager, $sparqlWhere)
{
    $countAllLabels = "
        SELECT (count(?label) AS ?count)
        WHERE {
            $sparqlWhere
        }
    ";
    
    $result = $resourceManager->query($countAllLabels);
    $total = $result->getArrayCopy()[0]->count->getValue();
    return $total;
}
