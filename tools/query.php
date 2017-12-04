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

/**
 * Script to perform certain CMD line queries on OpenSkos
 */
require dirname(__FILE__) . '/autoload.inc.php';

$opts = [
    'env|e=s' => 'The environment to use (defaults to "production")',
    'debug' => 'Show debug info.',
    'list' => 'Lists possible queries.',
    'noxl' => 'List all concepts missing a SkosXL prefLabel',
    'noxl-machine' => 'List all concepts missing a SkosXL prefLabel in machine readable format',
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

if ($OPTS->getOption('noxl') || $OPTS->getOption('noxl-machine') ) {

    listNonXLConcepts();
    exit;
}


$logger->info("Done!");
?>
<?php
    function getQueryItemsWithoutLabels()
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
MY_SPARQL;

        $queryOut = sprintf(    $queryOut,
                                \OpenSkos2\Namespaces\Rdf::TYPE,
                                 'http://www.w3.org/2004/02/skos/core#Concept',
                                    \OpenSkos2\Namespaces\Skos::PREFLABEL,
                                    \OpenSkos2\Namespaces\OpenSkos::STATUS,
                                    \OpenSkos2\Namespaces\SkosXl::PREFLABEL
            );
        return $queryOut;
    }

    function listNonXLConcepts()
    {
        global $diContainer, $logger, $OPTS;

        $formatString = "\033[1m<%s>\033[0m\n[%s]  '%s'\n\n";       //Default to human readable


        if ($OPTS->getOption('noxl-machine')) {
            $formatString = "<%s>\t%s\n";
            $formatString = "<%s>\t%s\t%s\n";
        }



        /* @var $resourceManager \OpenSkos2\Rdf\ResourceManagerWithSearch */
        $resourceManager = $diContainer->make('\OpenSkos2\Rdf\ResourceManagerWithSearch');

        /*
         * Query anything with Skos::Preflabel but no SkosXL::Preflabel
         */
        $sparql = getQueryItemsWithoutLabels();
        $results = $resourceManager->query($sparql);

        //Tell the user the news
       foreach($results as $row){
           printf($formatString,
                    $row->subject->getUri(),
                    $row->status->getValue(),
                    $row->coreLabel->getValue()
           );

       }

    }
?>
