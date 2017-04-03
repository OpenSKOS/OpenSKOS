<?php



require 'autoload.inc.php';
require 'Zend/Console/Getopt.php';
require_once 'utils_functions.php';

$opts = array(
  'help|?' => 'Print this usage message',
  'tenant=s' => 'tenants code',
  'key=s' => 'Api key for the Admin account',
  'setUri=s' => 'set Uri',
  'title=s' => 'schema title',
  'description=s' => 'schema description',
  'uuid=s' => "uuid",
  'uri=s' => "uri",
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

$must_params = ['tenant', 'key', 'uri', 'uuid', 'title', 'setUri'];
foreach ($must_params as $name) {
  if (null === $OPTS->$name) {
    fwrite(STDERR, "missing required `" . $name . "` argument\n");
    exit(1);
  }
}

check_if_admin($OPTS->tenant, $OPTS->key, $resourceManager, $model);

fwrite(STDOUT, "\n\n\n Strating script ... \n ");
switch ($action) {
  case 'create':

    //create concept scheme
    insert_conceptscheme($OPTS->setUri, $resourceManager, $OPTS->uri, $OPTS->uuid, $OPTS->title, $OPTS->description);
    fwrite(STDOUT, 'A concept scheme  has been created in the triple store with this uri: ' . $OPTS->uri . "\n");
    fwrite(STDOUT, 'To check: try GET <baseuri>/api/conceptscheme?id=' . $OPTS->uri . "\n");

    break;
  default:
    fwrite(STDERR, "unkown action `{$action}`\n");
    exit(1);
}

exit(0);

// php conceptscheme.php --tenant=example --key=xxx --setUri=https://set01/set01abc --uri=https://schema01/ --description="test schema" --uuid=schema01abc  --title=testschema01  create  
   