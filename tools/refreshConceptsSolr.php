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

/* @var $conceptManager \OpenSkos2\ConceptManager */
$conceptManager = $diContainer->make('OpenSkos2\ConceptManager');

/* @var $solrResourceManager \OpenSkos2\Solr\ResourceManager */
$solrResourceManager = $diContainer->make('\OpenSkos2\Solr\ResourceManager');
$solrResourceManager->setIsNoCommitMode(true);

$offset = 0;
$limit = 200;
$counter = 0;
do {
    $concepts = $conceptManager->search('*', $limit, $offset);
    
    foreach ($concepts as $concept) {
        $counter ++;
        $logger->debug($concept->getUri());
        $solrResourceManager->delete($concept);
        $solrResourceManager->insert($concept);
    }
    $offset += $limit;
} while (count($concepts) > 0);

$logger->info('Processed: ' . $counter);

$logger->info("Done!");
