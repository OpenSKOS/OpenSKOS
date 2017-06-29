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
 * @copyright  Copyright (c) 2015 Pictura Database Publishing. (http://www.pictura-dp.nl)
 * @author     Alexandar Mitsev
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 */
/**
 * Script to migrate the data from SOLR to Jena run as following:
 * Run the file as : php tools/migrate.php --endpoint http://<host>:<port>/path/core/select --tenant=<code>
 */
require_once dirname(__FILE__) . '/autoload.inc.php';

use OpenSkos2\Namespaces\Rdf;
use OpenSkos2\Tenant;
use OpenSkos2\Set;
use OpenSkos2\Rdf\Uri;


// VOORBEELD
//php migrate_tenant_collection.php --db-hostname=localhost --db-database=geheim --db-password=geheim --db-username=root --debug=1


$opts = [
    'env|e=s' => 'The environment to use (defaults to "production")',
    'db-hostname=s' => 'Origin database host',
    'db-database=s' => 'Origin database name',
    'db-username=s' => 'Origin database username',
    'db-password=s' => 'Origin database password',
    'tenant=s' => 'Tenant code to migrate',
    'modified|m=s' => 'Fetch only those modified after that date.',
    'tenantname=s' => 'Name of the organisaton.',
    'debug' => "If debug mode is on"
];

try {
    $OPTS = new Zend_Console_Getopt($opts);
} catch (Zend_Console_Getopt_Exception $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    echo str_replace('[ options ]', '[ options ] action', $OPTS->getUsageMessage());
    exit(1);
}
require_once dirname(__FILE__) . '/bootstrap.inc.php';

require_once 'utils_functions.php';

validateOptions($OPTS);
$dbSource = \Zend_Db::factory('Pdo_Mysql', array(
        'host' => $OPTS->getOption('db-hostname'),
        'dbname' => $OPTS->getOption('db-database'),
        'username' => $OPTS->getOption('db-username'),
        'password' => $OPTS->getOption('db-password'),
    ));
$dbSource->setFetchMode(\PDO::FETCH_OBJ);

/* @var $diContainer DI\Container */
$diContainer = Zend_Controller_Front::getInstance()->getDispatcher()->getContainer();
/**
 * @var $resourceManager \OpenSkos2\Rdf\ResourceManager
 */
$resourceManager = $diContainer->make('\OpenSkos2\Rdf\ResourceManager');
$tenantManager = $diContainer->make('\OpenSkos2\TenantManager');


$logger = new \Monolog\Logger("Logger");
$logLevel = \Monolog\Logger::INFO;
if ($OPTS->getOption('debug')) {
    $logLevel = \Monolog\Logger::DEBUG;
}
$logger->pushHandler(new \Monolog\Handler\ErrorLogHandler(
    \Monolog\Handler\ErrorLogHandler::OPERATING_SYSTEM, $logLevel
));

if ($OPTS->getOption('debug')) {
    $logger->info("Purging triple store: tenants");
    $tenantURIs = $resourceManager->fetchSubjectForObject(Rdf::TYPE, new Uri(Tenant::TYPE));
    foreach ($tenantURIs as $tenantURI) {
        $tenantManager->delete(new Uri($tenantURI));
    }
    $logger->info("Purging triple store: sets");
    $setURIs = $resourceManager->fetchSubjectForObject(Rdf::TYPE, new Uri(Set::TYPE));
    foreach ($setURIs as $setURI) {
        $resourceManager->delete(new Uri($setURI));
    }
}

$tenantCode = $OPTS->tenant;
$tenantName = $OPTS->tenantname;
if (empty($tenantName)) {
    $tenantName = $tenantCode;
}

$tenantCache = new Institutions($dbSource);
$tenants = $tenantCache->validateInstitutions($resourceManager);

$logger->info("Validating institutions, creating triple store tenants");

foreach ($tenants as $tenant) {
    insertResource($resourceManager, $tenant);
}

$logger->info("Validating collections, creating  triple store sets");

$collectionCache = new Collections($dbSource);
$sets = $collectionCache->validateCollections($resourceManager);
foreach ($sets as $set) {
    insertResource($resourceManager, $set);
}

$logger->info("Done");