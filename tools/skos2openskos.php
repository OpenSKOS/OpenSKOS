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

// VOORBEELD: php skos2openskos.php --setUri=http://htdl/clavas-org/set --userUri=http://localhost:89/clavas/public/api/users/4d1140e5-f5ff-45da-b8de-3d8a2c28415f --file=clavas-organisations.xml

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
$conceptManager = $diContainer->get('OpenSkos2\ConceptManager');
$personManager = $diContainer->get('OpenSkos2\PersonManager');

$person = $resourceManager->fetchByUri($OPTS->userUri, \OpenSkos2\Person::TYPE);
$set = $resourceManager->fetchByUri($OPTS->setUri, \OpenSkos2\Set::TYPE);
$publisher = $set->getProperty(\OpenSkos2\Namespaces\DcTerms::PUBLISHER);
if (count($publisher)<1) {
  echo str_replace('Something went very wrong: the set '. $OPTS->setUri. 'does not have a publisher.');
  exit(1); 
}
$tenant = $resourceManager->fetchByUri($publisher[0]->getUri(), \OpenSkos2\Tenant::TYPE);


$logger = new \Monolog\Logger("Logger");
$logger->pushHandler(new \Monolog\Handler\ErrorLogHandler());


$check_concept_references = null;

echo "First round. (The referecne to a concept via relations, hasTopConcept or member are not validated.) \n";

 /** Recall Message's constructor parameters to see what is going on
   /**
   * Message constructor.
   * @param $person
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
$message = new \OpenSkos2\Import\Message( // $isRemovingDanglingConceptReferencesRound = false
  $person, $OPTS->file, new \OpenSkos2\Rdf\Uri($OPTS->setUri), true, OpenSKOS_Concept_Status::CANDIDATE, false, true, false, 'en', false, false
);
$importer = new \OpenSkos2\Import\Command($resourceManager, $conceptManager, $personManager, $tenant);

$importer->setLogger($logger);

$importer->handle($message);


echo "Done\n";

