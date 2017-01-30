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
 * Index Jena to SOLR run this if the search is out of sync
 */

require dirname(__FILE__) . '/autoload.inc.php';

$options = [
    'env|e=s'   => 'The environment to use (defaults to "production")',
    'uri|u=s'   => 'Index single uri e.g -u http://data.beeldengeluid.nl/gtaa/356512',
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

$uri = $OPTS->getOption('uri');

/* @var $conceptManager \OpenSkos2\ConceptManager */
$conceptManager = $diContainer->make('\OpenSkos2\ConceptManager');

$total = getTotal($conceptManager, $uri);
$rows = 1000;

if ($uri) {
    
    $fetchConcepts = "DESCRIBE <$uri>";
    
} else {
    
    $fetchConcepts = "
        DESCRIBE ?subject
        WHERE {
          ?subject ?predicate <http://www.w3.org/2004/02/skos/core#Concept>
        }
        LIMIT $rows
    ";
}

/* @var $solrResourceManager \OpenSkos2\Solr\ResourceManager */
$solrResourceManager = $diContainer->make('\OpenSkos2\Solr\ResourceManager');
$solrResourceManager->setIsNoCommitMode(true);

$offset = 0;
while ($offset < $total) {

    $concepts = $conceptManager->fetchQuery($fetchConcepts . ' OFFSET ' . $offset);
    
    $offset = $offset + $rows;
    
    foreach ($concepts as $concept) {

        $logger->debug($concept->getUri());

        try {
            $solrResourceManager->insert($concept);
        } catch (\Exception $exc) {
            echo $exc->getMessage() . PHP_EOL;
            echo $exc->getTraceAsString() . PHP_EOL;
        }

    }
}

$solrResourceManager->commit();

$logger->info('Processed: ' . $total);
$logger->info("Done!");

/**
 * Get total amount of concepts
 * 
 * @param \OpenSkos2\ConceptManager $conceptManager
 * @param type $uri
 * @return int
 */
function getTotal(\OpenSkos2\ConceptManager $conceptManager, $uri = null) {

    if ($uri) {
        return 1;
    }

    $countAllConcepts = "
        prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#>
        prefix owl: <http://www.w3.org/2002/07/owl#>

        SELECT (count(?subject) AS ?count)
        WHERE {
          ?subject ?predicate <http://www.w3.org/2004/02/skos/core#Concept>
        }
    ";

    $result = $conceptManager->query($countAllConcepts);
    $total = $result->getArrayCopy()[0]->count->getValue();
    return $total;
}