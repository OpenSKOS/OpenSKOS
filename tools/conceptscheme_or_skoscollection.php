<?php

use OpenSkos2\Namespaces\Skos;

require 'autoload.inc.php';
require 'Zend/Console/Getopt.php';
require_once 'utils_functions.php';


fwrite(STDOUT, "\n\n\n Starting script consceptscheme or skoscollection... \n ");


$opts = array(
  'help|?' => 'Print this usage message',
  'tenant=s' => 'tenants code',
  'key=s' => 'Api key for the Admin account',
  'setUri=s' => 'set Uri',
  'title=s' => 'schema title',
  'description-s' => 'schema description',
  'uuid=s' => "uuid",
  'uri=s' => "uri",
  'restype=w' => "resource type to handle in this script, for now can be scheme or skoscollection"
);
$OPTS = new Zend_Console_Getopt($opts);
if ($OPTS->help) {
  echo str_replace('[ options ]', '[ options ] action', $OPTS->getUsageMessage());
  exit(0);
}


$args = $OPTS->getRemainingArgs();
$action = $args[count($args) - 1];


require dirname(__FILE__) . '/bootstrap.inc.php';

/* @var $diContainer Container */
$diContainer = Zend_Controller_Front::getInstance()->getDispatcher()->getContainer();

/**
 * @var $resourceManager \OpenSkos2\Rdf\ResourceManager
 */
$resourceManager = $diContainer->make('\OpenSkos2\Rdf\ResourceManager');
$model = new OpenSKOS_Db_Table_Users();

$must_params = ['tenant', 'key', 'uri', 'uuid', 'title', 'setUri', 'restype'];
foreach ($must_params as $name) {
  if (null === $OPTS->$name) {
    fwrite(STDERR, "missing required `" . $name . "` argument\n");
    exit(1);
  }
}

check_if_admin($OPTS->tenant, $OPTS->key, $resourceManager, $model);

switch ($OPTS->restype) {
  case 'scheme':
    $rdftype = Skos::CONCEPTSCHEME;
    break;
  case 'skoscollection':
    $rdftype = Skos::SKOSCOLLECTION;
    break;
  default:
    fwrite(STDERR, "resource-type parameter `restype` can be set to either `scheme` or `skoscollection`\n");
    exit(1);
}

switch ($action) {
  case 'create':

    //create resource
    insert_conceptscheme_or_skoscollection($OPTS->setUri, $resourceManager, $OPTS->uri, $OPTS->uuid, $OPTS->title, $OPTS->description, $rdftype);
    fwrite(STDOUT, "A $OPTS->restype  has been created in the triple store with this uri: $OPTS->uri \n");
    fwrite(STDOUT, 'To check: try GET <baseuri>/api/conceptscheme?id=' . $OPTS->uri . "\n");

    break;
  default:
    fwrite(STDERR, "unkown (not yet implemented) action `{$action}`\n");
    exit(1);
}

exit(0);

// php conceptscheme_or_skoscollection.php --tenant=example --key=xxx --setUri=https://set01/set01abc --uri=https://skoscollection02/ --description="test collection 2" --uuid=skoscollection02abc  --title="test collection 02"  --restype=skoscollection create  
   