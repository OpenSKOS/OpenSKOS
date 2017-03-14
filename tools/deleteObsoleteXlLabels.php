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

if ($OPTS->getOption('days') !== NULL) {
    $maxLabelAgeInDays = (int)$OPTS->getOption('days');
} else {
    $maxLabelAgeInDays = 7;
}

/* @var $labelManager \OpenSkos2\SkosXl\LabelManager */
$labelManager = $diContainer->make('\OpenSkos2\SkosXl\LabelManager');

// Used to record time it takes for the script execution
$scriptStart = microtime(true);

// The where clause used to filter out labels
$sparqlWhere = '
    ?label <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://www.w3.org/2008/05/skos-xl#Label>
';

$total = getTotal($labelManager, $sparqlWhere);

if ($OPTS->getOption('verbose')) {
    $timeToCount = microtime(true) - $scriptStart;
    echo PHP_EOL . 'Time to count labels: ' . $timeToCount . 's' . PHP_EOL;
    $processTime = 0;
}

$rows = 1000;
$fetchLabels = "
    DESCRIBE ?label
    WHERE {
        $sparqlWhere
    }
    LIMIT $rows
";

// @TODO: Think of a way to globally define this list so in future when new 
// default params are added to the label this script will continue to work
$defaultXlLabelParameters = [
    \OpenSkos2\Namespaces\Rdf::TYPE,
    \OpenSkos2\Namespaces\OpenSkos::TENANT,
    \OpenSkos2\Namespaces\DcTerms::MODIFIED,
    \OpenSkos2\Namespaces\SkosXl::LITERALFORM
];


$offset = 0;
$notLinkedLabelsCount = 0;
$deletedLabelsCount = 0;
while ($offset < $total) {

    $labels = $labelManager->fetchQuery($fetchLabels . ' OFFSET ' . $offset);
    $offset = $offset + $rows;
    $labelsToDelete = [];
    
    foreach ($labels as $label) {
        /* @var $label \OpenSkos2\SkosXl\Label */
        
        if ($labelManager->ask('
            ?concept ?predicate <' . $label->getUri() . '> .
            ?concept <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://www.w3.org/2004/02/skos/core#Concept>
        ')) {
            // Skip labels that are linked to concepts
            continue;
        }
        
        $notLinkedLabelsCount++;

        try {
            if ($maxLabelAgeInDays > 0) {
                // We will not delete labels modified in less than 1 week
                // We will still delete labels with no modified date!!!
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

            // If we have not continued until now, the label is to be deleted
            $labelsToDelete[] = $label;
            $deletedLabelsCount++;
            
        } catch (\Exception $exc) {
            echo $exc->getMessage() . PHP_EOL;
            echo $exc->getTraceAsString() . PHP_EOL;
        }
    }
    
//    $labelManager->setIsNoCommitMode(true);
//    foreach ($labelsToDelete as $label) {
//        // Delete from Jena and Solr
//        $labelManager->delete($label);
//    }
//    // Commit to Solr
//    $labelManager->commit();
        
    if ($OPTS->getOption('verbose')) {
        $pageTime = round(microtime(true) - $scriptStart - $timeToCount - $processTime, 3);
        $processTime = round(microtime(true) - $scriptStart - $timeToCount, 3);
        $count = count($labelsToDelete);
        
        echo "Offset: $offset, toDelete: $count, pageTime: $pageTime, processingTime: $processTime" . PHP_EOL;
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
 * Get total amount of concepts
 * @param \OpenSkos2\Rdf\ResourceManagerWithSearch $labelManager
 * @return int
 */
function getTotal(\OpenSkos2\Rdf\ResourceManagerWithSearch $labelManager, $sparqlWhere)
{
    $countAllLabels = "
        SELECT (count(?label) AS ?count)
        WHERE {
            $sparqlWhere
        }
    ";
    
    $result = $labelManager->query($countAllLabels);
    $total = $result->getArrayCopy()[0]->count->getValue();
    return $total;
}
