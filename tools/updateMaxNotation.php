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
 */

/*
 * README
 *
 * Scan SOLR to reread the current maximum notation from Solr, and write it to the SQL database tracking the notation
 * It will be necessary to run this once on pre-Meertens Merge installations to fill the max notations database
 */

require dirname(__FILE__) . '/autoload.inc.php';

$options = [
    'env|e=s'   => 'The environment to use (defaults to "production")',
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

// Used to record time it takes for the script execution
$scriptStart = microtime(true);

/* @var $diContainer Container */
$diContainer = Zend_Controller_Front::getInstance()->getDispatcher()->getContainer();

$solrResourceManager = $diContainer->make('\OpenSkos2\Solr\ResourceManager');

$resourceManager = $diContainer->make('\OpenSkos2\Rdf\ResourceManager');
$model = new OpenSKOS_Db_Table_MaxNumericNotation();

$tenantManager = $diContainer->get('OpenSkos2\TenantManager');
$allTenants = $tenantManager->getAllTenants();


foreach ($allTenants as $tenant) {
    //Call up the highest value from SOLR
    $max = $solrResourceManager->getMaxFieldValue(
        sprintf('tenant:"%s"', $tenant['code'] ),
        'max_numeric_notation'
    );


    $row = $resourceManager->fetchRowWithRetries($model, sprintf('tenant_code = "%s"', $tenant['code']));

    if (null !== $row) {
        if($row->max_numeric_notation == $max){
            $logger->info(sprintf(
                "Record already exists for tenant '%s' with the correct max notation value of %d",
                $tenant['code'],
                $max
            ));

        }
        else {
            //This row existed. Update max numenric value
            $logger->info(sprintf(
                "Record already exists for tenant '%s' with incorrect max notation. Setting to %d",
                $tenant['code'],
                $max
            ));
            $model->update(array('max_numeric_notation' => $max), sprintf('id = %d', $row->id));
        }
    }
    else{
        // create user
        $model->createRow(array(
            'tenant_code' => $tenant['code'],
            'max_numeric_notation' => $max
        ))->save();

        $logger->info(sprintf(
            "Creating record for tenant '%s' and setting max notation to %d",
            $tenant['code'],
            $max
        ));
    }


}






return intval($max);





?>







