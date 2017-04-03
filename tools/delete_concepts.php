<?php


use DI\Container;
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Namespaces\Rdf;

require 'autoload.inc.php';
require 'Zend/Console/Getopt.php';


$opts = [
  'key=s' => 'user key',
  'tenant=s' => 'tenant code',
];

$OPTS = new Zend_Console_Getopt($opts);

require dirname(__FILE__) . '/bootstrap.inc.php';

/* @var $diContainer Container */
$diContainer = Zend_Controller_Front::getInstance()->getDispatcher()->getContainer();

$resourceManager = $diContainer->make('\OpenSkos2\Rdf\ResourceManager');


// authorisation check 

if (null === $OPTS->key) {
  fwrite(STDERR, "missing required `key` argument\n");
  exit(1);
}

if (null === $OPTS->tenant) {
  fwrite(STDERR, "missing required `tenant` argument\n");
  exit(1);
}
  
$model = new OpenSKOS_Db_Table_Users();
$admin = $resourceManager->fetchRowWithRetries($model, 'apikey = ' . $model->getAdapter()->quote($OPTS->key) . ' '
  . 'AND tenant = ' . $model->getAdapter()->quote($OPTS->tenant)
);
if (null === $admin) {
  fwrite(STDERR, 'There is no user with the key ' . $OPTS->key . ' in the tenant with the code ' . $OPTS->tenant . "\n");
  exit(1);
}
if ($admin->role !== OpenSKOS_Db_Table_Users::USER_ROLE_ADMINISTRATOR) {
  fwrite(STDERR, "The user with the key " . $OPTS->key . ' is not the administrator of the tenant with the code ' . $OPTS->code . "\n");
  exit(1);
}
// end authorisation check 

$conceptURIs = $resourceManager->fetchSubjectWithPropertyGiven(Rdf::TYPE, '<' . Skos::CONCEPT . '>', null);
$concepts = $resourceManager->fetchByUris($conceptURIs, Skos::CONCEPT);
foreach ($concepts as $concept) {
  $resourseManager->delete($concept, Skos::CONCEPT);
  $resourseManager->deleteReferencesToObject($concept);
}


