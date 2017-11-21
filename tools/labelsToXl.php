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
 * Script to index the solr from jena manually.
 * No need for regular use - just in case of some changes in the solr schema. 
 * Run the file as : php tools/indexSolr.php -e environment
 */
require dirname(__FILE__) . '/autoload.inc.php';

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

/* @var $resourceManager \OpenSkos2\Rdf\ResourceManagerWithSearch */
$resourceManager = $diContainer->make('OpenSkos2\Rdf\ResourceManagerWithSearch');

/* @var $conceptManager \OpenSkos2\ConceptManager */
$conceptManager = $diContainer->make('OpenSkos2\ConceptManager');

/* @var $labelHelper OpenSkos2\Concept\LabelHelper */
$labelHelper = $diContainer->make('OpenSkos2\Concept\LabelHelper');

$query = '-status:deleted';

// Should concepts which have pref label xl already be skipped.
$skipDone = $OPTS->getOption('skipDone');
if (!empty($skipDone)) {
    $query .= ' AND -s_prefLabelXl:*';
}

// Process only concpets modified after the specified date.
$modifiedSince = $OPTS->getOption('modified');
if (!empty($modifiedSince)) {
    $query .= ' AND d_modified:[' . $modifiedSince .  ' TO *]';
}

$offset = 0;
$limit = 200;
$counter = 0;
do {
    try {
        $concepts = $conceptManager->search($query, $limit, $offset, $numFound);

        $logger->info('Total: ' . $numFound);
        
        if ($concepts->count() > 0) {
            $deleteResources = new \OpenSkos2\Rdf\ResourceCollection([]);
            $inserResources = new \OpenSkos2\Rdf\ResourceCollection([]);

            foreach ($concepts as $concept) {


                $counter ++;
                $logger->debug($concept->getUri());

                try {
                    $labelHelper->assertLabels($concept, true);

                    $insertAndDelete = $labelHelper->getLabelsForInsertAndDelete($concept);

                    $deleteResources->merge($insertAndDelete['delete']);
                    $inserResources->merge($insertAndDelete['insert']);

                    // Create concept only with xl labels to insert it as partial resource
                    $partialConcept = new \OpenSkos2\Concept($concept->getUri());
                    foreach (\OpenSkos2\Concept::$classes['SkosXlLabels'] as $xlProperty) {
                        $partialConcept->setProperties($xlProperty, $concept->getProperty($xlProperty));
                    }

                    $inserResources->append($partialConcept);
                } catch (\Exception $ex) {
                    $logger->warning(
                        'Problem with the labels for "' . $concept->getUri()
                        . '". The message is: ' . $ex->getMessage()
                    );
                }
            }

            foreach ($deleteResources as $deleteResource) {
                $resourceManager->delete($deleteResource);
            }

            $resourceManager->extendCollection($inserResources);
        }
    } catch (\Exception $ex) {
        $logger->warning(
            'Problem processing concepts from ' . $offset . ', limit ' . $limit
            . '". The message is: ' . $ex->getMessage()
        );
    }

    
    if (!empty($skipDone)) {
        $offset = 0; // if we skip done we need to work without pagination
    } else {
        $offset += $limit;
    }
    
    $logger->info('Concepts processed so far: ' . $counter);
} while (count($concepts) > 0);

$logger->info('Concepts processed (total): ' . $counter);

$logger->info("Done!");
