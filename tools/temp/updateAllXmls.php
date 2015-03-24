<?php

/* 
 * Updates the status expired to status obsolete
 */

require_once 'Zend/Console/Getopt.php';
$opts = array(
	'env|e=s' => 'The environment to use (defaults to "production")',
);

try {
	$OPTS = new Zend_Console_Getopt($opts);
} catch (Zend_Console_Getopt_Exception $e) {
	fwrite(STDERR, $e->getMessage()."\n");
	echo str_replace('[ options ]', '[ options ] action', $OPTS->getUsageMessage());
	exit(1);
}

include dirname(__FILE__) . '/../bootstrap.inc.php';

// Allow loading of application module classes.
$autoloader = new OpenSKOS_Autoloader();
$mainAutoloader = Zend_Loader_Autoloader::getInstance();
$mainAutoloader->pushAutoloader($autoloader, array('Editor_', 'Api_'));


// Concepts
$conceptsCounter = 0;

$apiModel = Api_Models_Concepts::factory();

$start = 0;
$rows = 100;

$apiModel->setQueryParam('rows', $rows);
do {
    echo "Get {$rows} concepts starting from {$start}. \n";
    
    $apiModel->setQueryParam('start', $start);
    $response  = $apiModel->getConcepts('*:*', true);

    if (isset($response['response']['docs'])) {
        foreach ($response['response']['docs'] as $doc) {
            $concept = new Editor_Models_Concept(new Api_Models_Concept($doc));
            $concept->update([], [], true, true);
            $conceptsCounter ++;
            echo $concept['uuid'] . "\n";
        }
    }
    
    $start += $rows;
} while (count($response['response']['docs']) > 0);

echo $conceptsCounter . ' concepts were updated.' . "\n";