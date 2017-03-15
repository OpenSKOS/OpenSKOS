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
    'days|d=i'    => 'Labels modified in less that this mount will not be deleted. '
                 . 'Default is 7 days. Set to 0 to disable check.',
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

if ($OPTS->getOption('days') !== null) {
    $maxLabelAgeInDays = (int)$OPTS->getOption('days');
} else {
    $maxLabelAgeInDays = 7;
}

/* @var $labelManager \OpenSkos2\SkosXl\LabelManager */
$labelManager = $diContainer->make('\OpenSkos2\SkosXl\LabelManager');

/* @var $solrResourceManager \OpenSkos2\Solr\ResourceManager */
$solrResourceManager = $diContainer->make('\OpenSkos2\Solr\ResourceManager');

// Used to record time it takes for the script execution
$scriptStart = microtime(true);

$labelSort = ['uri' => 'ASC'];
$labelFilter = [
    'rdfTypeFilter' => 's_rdfType:"' . OpenSkos2\SkosXl\Label::TYPE . '"'
];

$total = getTotal($solrResourceManager, $labelFilter);

if ($OPTS->getOption('verbose')) {
    $timeToCount = microtime(true) - $scriptStart;
    $logger->info('Counted ' . $total . ' labels in ' . $timeToCount . 's');
    $processTime = 0;
}

$rows = 100;

// @TODO: Think of a way to globally define this list so in future when new 
// default params are added to the label this script will continue to work
$defaultXlLabelParameters = [
    \OpenSkos2\Namespaces\Rdf::TYPE,
    \OpenSkos2\Namespaces\OpenSkos::TENANT,
    \OpenSkos2\Namespaces\DcTerms::MODIFIED,
    \OpenSkos2\Namespaces\SkosXl::LITERALFORM
];

$page = 1;
$offset = 0;
$notLinkedLabelsCount = 0;
$deletedLabelsCount = 0;
while ($offset < $total) {

    $page++;
    $labelUris = $solrResourceManager->search('*:*', $rows, $offset, $total, $labelSort, $labelFilter);
    $offset = $offset + $rows;
    $labelsToDelete = [];
    
    foreach ($labelUris as $labelUri) {
        // @TODO Temp fix until data is cleaned from blank nodes.
        if (\OpenSkos2\Rdf\Uri::isUriTempGeneratedCheck($labelUri)) {
            continue;
        }
        
        if ($labelManager->ask(
            '
            ?concept ?predicate <' . $labelUri . '> .
            ?concept <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://www.w3.org/2004/02/skos/core#Concept>
            '
        )) {
            // Skip labels that are linked to concepts
            continue;
        }
        
        if ($labelManager->askForUri($labelUri) == false) {
            // Delete only from Solr
            $solrResourceManager->delete(new OpenSkos2\Rdf\Uri($labelUri));
            continue;
        }
        
        //Get the label from Jena
        /* @var $label OpenSkos2\SkosXl\Label */
        $label = $labelManager->fetchByUri($labelUri);
        
        $notLinkedLabelsCount++;
        
        try {
            // We will not delete labels modified in less than specified
            // We will still delete labels with no modified date!!!
            if ($maxLabelAgeInDays > 0) {
                $modified = $label->getProperty(OpenSkos2\Namespaces\DcTerms::MODIFIED);
                if (!empty($modified) && $modified[0] instanceof OpenSkos2\Rdf\Literal) {
                    /* @var $date DateTime */
                    $dateModified = $modified[0]->getValue();
                    $now = new DateTime(date('c'));
                    if ($dateModified->diff($now)->days < $maxLabelAgeInDays) {
                        continue;
                    }
                }
            }
            
            //We will not delete labels that contain extra information
            foreach ($label->getProperties() as $key => $value) {
                if (!in_array($key, $defaultXlLabelParameters)) {
                    // Continue for the outer cycle
                    continue(2);
                }
            }

            // If we have reached this part, the label is to be deleted
            $labelsToDelete[] = $label;
            $deletedLabelsCount++;
            $offset--;
            
        } catch (\Exception $exc) {
            echo $exc->getMessage() . PHP_EOL;
            echo $exc->getTraceAsString() . PHP_EOL;
        }
    }
    
    $labelManager->setIsNoCommitMode(true);
    foreach ($labelsToDelete as $label) {
        // Delete from Jena and Solr
        $labelManager->delete($label);
    }
    // Commit to Solr
    $labelManager->commit();
    
    if ($OPTS->getOption('verbose')) {
        $pageTime = round(microtime(true) - $scriptStart - $timeToCount - $processTime, 3);
        $processTime = round(microtime(true) - $scriptStart - $timeToCount, 3);
        $count = count($labelsToDelete);
        
        $logger->debug("Page: $page, Offset: $offset, Deleted: $count, pageTime: $pageTime, processingTime: $processTime");
    }
}

// report statistics
$totalScriptTime = microtime(true) - $scriptStart;
$logger->info('Script total time: ' . round($totalScriptTime, 3) . 's');
$logger->info('Total labels: ' . $total);
$logger->info('Labels not linked to concepts: ' . $notLinkedLabelsCount);
$logger->info('Removed labels: ' . $deletedLabelsCount);
$logger->info("Done!");

/**
 * Get total amount of labels
 * @param OpenSkos2\Solr\ResourceManager $solrResourceManager
 * @param array $labelFilter
 * @return int
 */
function getTotal(OpenSkos2\Solr\ResourceManager $solrResourceManager, $labelFilter)
{
    $total = 0;
    $solrResourceManager->search('*:*', 0, 0, $total, [], $labelFilter);
    
    return $total;
}
