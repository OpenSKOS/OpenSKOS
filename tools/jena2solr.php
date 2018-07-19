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
    'modified|m=s' => 'Index only those modified after that date.',
    'offset|o=s' => 'Offset to start indexing from (handy for resuming index action).',
    'skipDeleted|s' => 'Skip check of deleted files.',
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

// Used to record time it takes for the script execution
$scriptStart = microtime(true);

/* @var $resourceManager \OpenSkos2\Rdf\ResourceManagerWithSearch */
$resourceManager = $diContainer->make('\OpenSkos2\Rdf\ResourceManagerWithSearch');

$resourceTypes = [
    \OpenSkos2\Concept::TYPE,
    \OpenSkos2\SkosXl\Label::TYPE
];

$modifiedSince = $OPTS->getOption('modified');

$sparqlWhere = getSparqlWhereClause($resourceTypes, $modifiedSince);

if (empty($uri)) {
    $total = getTotal($resourceManager, $sparqlWhere);
} else {
    $total = 1;
}

$logger->info('Total in Jena: ' . $total);

$rows = 10000;

if ($uri) {
    $fetchResources = "DESCRIBE <$uri>";
} else {
    $fetchResources = "
        DESCRIBE ?subject
        WHERE {
          $sparqlWhere
        }
        LIMIT $rows
    ";
}

/* @var $solrResourceManager \OpenSkos2\Solr\ResourceManager */
$solrResourceManager = $diContainer->make('\OpenSkos2\Solr\ResourceManager');
$solrResourceManager->setIsNoCommitMode(true);

$doDeleteFromSolr = true;

if (!empty($modifiedSince)) {
    $logger->info('Modified option is set, so we wont check deleted for now.');
    $doDeleteFromSolr = false;
}

if ($OPTS->getOption('skipDeleted')) {
    $logger->info('Option skip deleted is set so we wont check deleted for now.');
    $doDeleteFromSolr = false;
}

if ($doDeleteFromSolr) {
    // Handle deleting from solr.
    $solrResourceManager->search('*:*', 0, 0, $solrTotal);
    $logger->info('Total in Solr: ' . $solrTotal);
    $logger->info('Start deleting from Solr');
    $deleted = removeDeletedResourcesFromSolr($solrResourceManager, $resourceManager, $logger, $scriptStart);
}

$logger->info('Start indexing to Solr');
// Update all resources from Jena to Solr
if ($OPTS->getOption('offset')) {
    $offset = (int)($OPTS->getOption('offset'));
} else {
    $offset = 0;
}

print "\n";
while ($offset < $total) {
    $counter = $offset;
    $pageStart = microtime(true);
    
    $resources = $resourceManager->fetchQuery($fetchResources . ' OFFSET ' . $offset);
    $interimTime = $pageTime = round(microtime(true) - $pageStart, 3);
    $logger->debug("Jena fetch in pageTime: $pageTime");

    $offset = $offset + $rows;

    foreach ($resources as $resource) {

        //$logger->debug($resource->getUri());

        try {
            $solrResourceManager->insert($resource);
        } catch (\Exception $exc) {
            echo $exc->getMessage() . PHP_EOL;
            echo $exc->getTraceAsString() . PHP_EOL;
        }
        if ($OPTS->getOption('verbose')) {
            print "\r";
            print " $counter " . spinner();
        }
        $counter++;
    }

    $solrResourceManager->commit();
    $pageTime = round(microtime(true) - $pageStart, 3);
    $logger->debug(sprintf("\nSolr Write in pageTime: %.4f", $pageTime - $interimTime));

    $commited =  count($resources);
    $pageTime = round(microtime(true) - $pageStart, 3);
    $processTime = round(microtime(true) - $scriptStart, 3);
    $logger->debug("Offset: $offset, Commited: $commited, pageTime: $pageTime, totalTime: $processTime");
    if ($OPTS->getOption('verbose')) {
        print "\n";
    }
}

$logger->debug('Total in Jena: ' . $total);

if (isset($solrTotal)) {
    $logger->debug('Total in Solr: ' . $solrTotal);
    $logger->debug('Deleted from Solr: ' . $deleted);
    if ($solrTotal != ($total + $deleted)) {
        $logger->notice(
            'There is mismatch between deleted, solr total and indexed counts. Maybe something went wrong.'
        );
        $logger->notice('Should be solrTotal = indexed + deleted');
    }
}

$logger->info("Done!");

/**
 * Get total amount of concepts
 * @param \OpenSkos2\Rdf\ResourceManagerWithSearch $resourceManager
 * @return int
 */
function getTotal(\OpenSkos2\Rdf\ResourceManagerWithSearch $resourceManager, $sparqlWhere)
{

    $countAll = "
        prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#>
        prefix owl: <http://www.w3.org/2002/07/owl#>

        SELECT (count(?subject) AS ?count)
        WHERE {
          $sparqlWhere
        }
    ";
    
    $result = $resourceManager->query($countAll);
    $total = $result->getArrayCopy()[0]->count->getValue();
    
    return $total;
}

function getSparqlWhereClause($resourceTypes, $modifiedSince = null)
{
    $whereClause = '';
    
    foreach ($resourceTypes as $resourceType) {
        if (!empty($whereClause)) {
            $whereClause .= ' UNION ';
        }
        $whereClause .= '{?subject <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <' . $resourceType . '> }';
    }
    
    if (!empty($modifiedSince)) {
        $whereClause .= '
            ?subject <http://purl.org/dc/terms/modified> ?timeModified
            FILTER (?timeModified > \'' . $modifiedSince . '\'^^xsd:dateTime)
        ';
    }
    
    return $whereClause;
}

/**
 * Remove all resources in Solr that do not match a resource in Jena
 * @param OpenSkos2\Solr\ResourceManager $solrResourceManager
 * @param OpenSkos2\Rdf\ResourceManagerWithSearch $resourceManager
 * @param Monolog\Logger $logger
 * @param int $scriptStart
 * @return int The number of deleted Solr resources
 */
function removeDeletedResourcesFromSolr(
    OpenSkos2\Solr\ResourceManager $solrResourceManager,
    OpenSkos2\Rdf\ResourceManagerWithSearch $resourceManager,
    Monolog\Logger $logger,
    $scriptStart
) {
    $total = 0;
    $solrResourceManager->search('*:*', 0, 0, $total);
    $rows = 100;
    $offset = 0;
    $deleted = 0;
    
    while ($offset < $total) {
        $pageStart = microtime(true);
        $deleted = 0;

        $resources = $solrResourceManager->search('*:*', $rows, $offset, $total, ['uri' => 'ASC']);
        $offset = $offset + $rows;

        foreach ($resources as $resource) {
            $ask = "<$resource> ?p ?o";
            if ($resourceManager->ask($ask) === false) {
                $solrResourceManager->delete(new OpenSkos2\Rdf\Uri($resource));
                $deleted++;
                $offset--;
            }
        }

        $pageTime = round(microtime(true) - $pageStart, 3);
        $processTime = round(microtime(true) - $scriptStart, 3);
        $logger->debug("Offset: $offset, Deleted: $deleted, pageTime: $pageTime, totalTime: $processTime");

        $solrResourceManager->commit();
    }
    
    return $deleted;
}

function spinner(){
    static $values = array('|', '/', '-', '\\');
    static $ptr = 0;

    $ptr++;
    if ($ptr == 4){
        $ptr = 0;
    }
    return $values[$ptr];
}
