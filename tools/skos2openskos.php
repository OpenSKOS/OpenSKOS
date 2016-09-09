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
    'userUri|u=s' => 'Uri of the user who is doing the import',
    'setUri=s' => 'Set uri'
);

try {
    $OPTS = new Zend_Console_Getopt($opts);
} catch (Zend_Console_Getopt_Exception $e) {
    fwrite(STDERR, $e->getMessage()."\n");
    echo str_replace('[ options ]', '[ options ] action', $OPTS->getUsageMessage());
    exit(1);
}

include dirname(__FILE__) . '/bootstrap.inc.php';

$old_time = time();
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

$importer = new \OpenSkos2\Import\Command($resourceManager);
$importer->setLogger($logger);
$message = new \OpenSkos2\Import\Message(
    $user, $OPTS->file, new \OpenSkos2\Rdf\Uri($OPTS->setUri), true, OpenSKOS_Concept_Status::CANDIDATE,
    true, false, 'en', false, false
);

$not_valid_resource_uris = $importer->handle($message);
$elapsed = time()-$old_time;
echo "\n time elapsed since start of import (sec): ". $elapsed . "\n";
$old_time = time();
//$not_valid_resource_uris = ["http://openskos.meertens.knaw.nl/Organisations/ea373664-6d46-48a3-b4e0-fce0db71777c", "http://openskos.meertens.knaw.nl/Organisations/9e33ff35-1955-4c41-93f1-2184f83b272c"];
require_once 'RemoveDanglingReferences.php';
\Tools\RemoveDanglingReferences::remove_dangling_references($resourceManager, $not_valid_resource_uris);
$elapsed = time()-$old_time;
echo "\n time elapsed since start of cleaning (sec) : ". $elapsed . "\n";

echo "done!";


//php skos2openskos.php --setUri=http://mertens/knaw/dataset_2216cd25-47d7-4f6a-ad5b-9cec0900b5ae --userUri=http://192.168.99.100/public/api/users/02173d52-a71f-4470-b77d-20c181139a38 --file=iso-language-639-3-clavas-incl-cs.xml