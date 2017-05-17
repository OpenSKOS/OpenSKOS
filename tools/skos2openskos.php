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
// Meertens:
// -- conceptManager in our version is not necessary, Resource manager is reposinsible for managing all sort of recourses.
// -- We do not have tenant as command line-parameter because we already have a setUri as a command-line parameter which is more precise, because a tenant may have few sets.
// tenant's Uri  is derived in "handle" of OpenSkos2\Import\Command from setUri (once, so it should not slow down import)
// -- because of this merging becomes a bit of a problem (Pictira'es code appeal to tenant quite a few times) and 
// it does make sence to keep two skos2openskos sets
// -- we alse refer set and tenant via their URI, not code. In the future a syntacti sugar we can use tenant and set code for user' concevnience

include dirname(__FILE__) . '/autoload.inc.php';

$opts = array(
  'env|e=s' => 'The environment to use (defaults to "production")',
  'file|f=s' => 'File to import',
  'userUri|u=s' => 'Uri of the user who is doing the import',
  'setUri=s' => 'Set uri',
);

try {
  $OPTS = new Zend_Console_Getopt($opts);
} catch (Zend_Console_Getopt_Exception $e) {
  fwrite(STDERR, $e->getMessage() . "\n");
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

$user = $resourceManager->fetchByUri($OPTS->userUri, \OpenSkos2\Namespaces\Foaf::PERSON);

$logger = new \Monolog\Logger("Logger");
$logger->pushHandler(new \Monolog\Handler\ErrorLogHandler());

$importer = new \OpenSkos2\Import\Command($resourceManager);
$importer->setLogger($logger);
$check_concept_references = null;

echo "First round. (The referecne from a concept to another concept via relations is not be validated.) \n";

 /** Recall Message's constructor parameters to see what is going on
   /**
   * Message constructor.
   * @param $user
   * @param $file
   * @param Uri $setUri
   * @param bool $ignoreIncomingStatus
   * @param string $importedConceptStatus
   * @param bool $isRemovingDanglingConceptReferencesRound
   * @param bool $noUpdates
   * @param bool $toBeChecked
   * @param string $fallbackLanguage
   * @param bool $clearSet
   * @param bool $deleteSchemes
   */
$message = new \OpenSkos2\Import\Message( // Do not include references to concepts, NoUpdate Mode 
  $user, $OPTS->file, new \OpenSkos2\Rdf\Uri($OPTS->setUri), true, OpenSKOS_Concept_Status::CANDIDATE, false, true, false, 'en', false, false
);

$not_valid_resource_uris = $importer->handle($message);
$elapsed = time() - $old_time;
echo "\n time elapsed since start of import (sec): " . $elapsed . "\n";
echo "The following " . count($not_valid_resource_uris) . " resources are not valid and not imported: \n";
foreach ($not_valid_resource_uris as $uri) {
  echo "\n " . $uri;
}
echo "\n First round is finished. The second round: the references to the non-valid concepts from other concepts via relations will be removed before submitting a concept for update. UpdateMode\n";

$message2 = new \OpenSkos2\Import\Message(// Include references to concepts, Update Mode 
  $user, $OPTS->file, new \OpenSkos2\Rdf\Uri($OPTS->setUri), true, OpenSKOS_Concept_Status::CANDIDATE, true, true, false, 'en', false, false
);
$not_valid_resource_uris2 = $importer->handle($message2);
$elapsed2 = time() - $old_time;
echo "\n time elapsed since start of the import : " . $elapsed2 . "\n";
echo "The following " . count($not_valid_resource_uris2) . " resources are not valid and not imported in the second round import: \n";
foreach ($not_valid_resource_uris2 as $uri) {
  echo "\n " . $uri;
}
echo "Done\n";

//php skos2openskos.php --setUri=http://htdl/clavas-org/set --userUri=http://host/clavas/public/api/users/a8db78a0-7b51-4914-a4a3-11d268d339ff --file=clavas-organisations.xml