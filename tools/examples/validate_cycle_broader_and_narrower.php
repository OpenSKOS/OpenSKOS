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
 */

namespace examples;

require dirname(__FILE__) . '/../autoload.inc.php';

use OpenSkos2\Namespaces\Skos;

$opts = array(
    'env|e=s' => 'The environment to use (defaults to "production")',
    'endpoint=s' => 'Solr endpoint to fetch data from',
    'tenant=s' => 'Tenant to migrate',
);

try {
    $OPTS = new \Zend_Console_Getopt($opts);
} catch (\Zend_Console_Getopt_Exception $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    echo str_replace('[ options ]', '[ options ] action', $OPTS->getUsageMessage());
    exit(1);
}

require dirname(__FILE__) . '/../bootstrap.inc.php';

/* @var $diContainer DI\Container */
$diContainer = \Zend_Controller_Front::getInstance()->getDispatcher()->getContainer();

/* @var $resourceManager \OpenSkos2\Rdf\ResourceManager */
$resourceManager = $diContainer->get('OpenSkos2\Rdf\ResourceManager');

$logger = new \Monolog\Logger("Logger");
$logger->pushHandler(new \Monolog\Handler\ErrorLogHandler());

$concept = new \OpenSkos2\Concept('http://data.cultureelerfgoed.nl/semnet/5c51c121-fc97-489c-a4d1-239254b94270');
$concept->addProperty(Skos::PREFLABEL, new \OpenSkos2\Rdf\Literal('bouwelementen'));
//$concept->addProperty(Skos::BROADER, new \OpenSkos2\Rdf\Uri('http://data.cultureelerfgoed.nl/semnet/89b28aa0-e92e-4abc-9615-8526388d7d32'));
$concept->addProperty(Skos::BROADER, new \OpenSkos2\Rdf\Uri('http://data.cultureelerfgoed.nl/semnet/9537d9bb-d842-4c31-bb3a-71677224eeb3'));
$concept->addProperty(Skos::NARROWER, new \OpenSkos2\Rdf\Uri('http://data.cultureelerfgoed.nl/semnet/9537d9bb-d842-4c31-bb3a-71677224eeb3'));

$validator = new \OpenSkos2\Validator\Concept\CycleInBroaderAndNarrower();
$validator->setResourceManager($resourceManager);
$isValid = $validator->validate($concept);
var_dump($isValid);
