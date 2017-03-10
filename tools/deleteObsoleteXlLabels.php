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

/* @var $labelManager \OpenSkos2\SkosXl\LabelManager */
$labelManager = $diContainer->make('\OpenSkos2\SkosXl\LabelManager');

// The where clause used to filter out labels
$sparqlWhere = '
    ?label <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://www.w3.org/2008/05/skos-xl#Label>
';

$timerStart = microtime(true);
$total = getTotal($labelManager, $sparqlWhere);
$countTime = microtime(true) - $timerStart;
echo PHP_EOL . 'Time to count labels: ' . $countTime . 's' . PHP_EOL;

$rows = 100;

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

$labelsToDelete = [];
$processTime = 0;

$offset = 0;
while ($offset < $total) {

    $labels = $labelManager->fetchQuery($fetchLabels . ' OFFSET ' . $offset);
    $offset = $offset + $rows;
    
    foreach ($labels as $label) {
        /* @var $label \OpenSkos2\SkosXl\Label */
        //$logger->debug($label->getUri());
        
        if ($labelManager->ask('
            ?concept ?predicate <' . $label->getUri() . '> .
            ?concept <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://www.w3.org/2004/02/skos/core#Concept>
        ')) {
            // Skip labels that are linked to concepts
            continue;
        }

        try {
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
            
            //We will not delete labels that contain extra information
            foreach ($label->getProperties() as $key => $value) {
                if (!in_array($key, $defaultXlLabelParameters)) {
                    continue;
                }
            }

            // If we have not continued until now, the label is to be deleted
            $labelsToDelete[] = $label;
            
        } catch (\Exception $exc) {
            echo $exc->getMessage() . PHP_EOL;
            echo $exc->getTraceAsString() . PHP_EOL;
        }
    }
        
    $pageTime = round(microtime(true) - $timerStart - $countTime - $processTime, 3);
    $processTime = round(microtime(true) - $timerStart - $countTime, 3);
    $count = count($labelsToDelete);
    
    echo "Offset: $offset, toDelete: $count, pageTime: $pageTime, processingTime: $processTime" . PHP_EOL;
}

$scriptTime = microtime(true) - $timerStart;

echo PHP_EOL . 'Labels to delete: ' . count($labelsToDelete) . PHP_EOL;
echo PHP_EOL . 'Fetch time: ' . $fetchTotalTime . PHP_EOL;
echo PHP_EOL . 'Script total time: ' . $scriptTime . PHP_EOL;
die;
//
//$labelManager->setIsNoCommitMode(true);
//foreach ($labelsToDelete as $label) {
//    // Delete from Jena and Solr
//    $labelManager->delete($label);
//}
//// Commit to Solr
//$labelManager->commit();

$logger->info('Labels not linked to concepts: ' . $total);
$logger->info('Removed labels: ' . count($labelsToDelete));
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
