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
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   OpenSKOS
 * @package    OpenSKOS
 * @copyright  Copyright (c) 2015 Pictura Database Publishing. (http://www.pictura-dp.nl)
 * @author     Alexandar Mitsev
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 */
include dirname(__FILE__) . '/autoload.inc.php';

$opts = array(
    'env|e=s' => 'The environment to use (defaults to "production")',
    'file|f=s' => 'File to import',
    'userUri|u=s' => 'Uri of the user that is doing the import'
);

try {
    $OPTS = new Zend_Console_Getopt($opts);
} catch (Zend_Console_Getopt_Exception $e) {
    fwrite(STDERR, $e->getMessage()."\n");
    echo str_replace('[ options ]', '[ options ] action', $OPTS->getUsageMessage());
    exit(1);
}

include dirname(__FILE__) . '/bootstrap.inc.php';

// Test....

/* @var $diContainer DI\Container */
$diContainer = Zend_Controller_Front::getInstance()->getDispatcher()->getContainer();

/**
 * @var $resourceManager \OpenSkos2\Rdf\ResourceManager
 */
$resourceManager = $diContainer->get('OpenSkos2\Rdf\ResourceManager');
$conceptManager = $diContainer->get('OpenSkos2\ConceptManager');
$user = $resourceManager->fetchByUri($OPTS->userUri, \OpenSkos2\Namespaces\Foaf::PERSON);

$logger = new \Monolog\Logger("Logger");
$logger->pushHandler(new \Monolog\Handler\ErrorLogHandler());

$importer = new \OpenSkos2\Import\Command($resourceManager, $conceptManager);
$importer->setLogger($logger);
$message = new \OpenSkos2\Import\Message(
    $user, $OPTS->file, new \OpenSkos2\Rdf\Uri('http://example.com/collection#1'), true, OpenSKOS_Concept_Status::CANDIDATE,
    false, false, 'en', true, false
);

$importer->handle($message);




echo "done!";
