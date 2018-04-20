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
require dirname(__FILE__) . '/../autoload.inc.php';

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

require dirname(__FILE__) . '/../bootstrap.inc.php';

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

/* @var $conceptManager OpenSkos2\ConceptManager */
$conceptManager = $diContainer->make('OpenSkos2\ConceptManager');

/* @var $personManager OpenSkos2\PersonManager */
$personManager = $diContainer->make('OpenSkos2\PersonManager');

// Manually extracted list of concepts with broken author
/* Use this query

SELECT ?concept
WHERE
{
	?concept <http://purl.org/dc/terms/creator> ?creator . 
  	?concept <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://www.w3.org/2004/02/skos/core#Concept> .
	FILTER(isLiteral(?creator))  
}

*/
$concepts = [
    'http://data.beeldengeluid.nl/gtaa/1672243',
    'http://data.beeldengeluid.nl/gtaa/1672244',
    'http://data.beeldengeluid.nl/gtaa/1672245',
    'http://data.beeldengeluid.nl/gtaa/1672246',
    'http://data.beeldengeluid.nl/gtaa/1672247',
    'http://data.beeldengeluid.nl/gtaa/1672248',
    'http://data.beeldengeluid.nl/gtaa/1672299',
    'http://data.beeldengeluid.nl/gtaa/1672300',
    'http://data.beeldengeluid.nl/gtaa/1672301',
    'http://data.beeldengeluid.nl/gtaa/1672348',
    'http://data.beeldengeluid.nl/gtaa/1672349',
    'http://data.beeldengeluid.nl/gtaa/1672350'
];

$logger->info('Start');
$total = count($concepts);

// David's user
$nullPerson = new \OpenSkos2\Person();
foreach ($concepts as $i => $uri) {
    /* @var $concept \OpenSkos2\Concept */
    $concept = $conceptManager->fetchByUri($uri);
    
    // Force update dcterms:creator and dc:creator as proper values
    $concept->resolveCreator($nullPerson, $personManager);
    $conceptManager->replace($concept);
    
    echo "Processed $i of $total\r";
}
echo PHP_EOL;
$logger->info('Done');
