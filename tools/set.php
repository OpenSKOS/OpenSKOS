<?php

use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Namespaces\Org;
use OpenSkos2\Namespaces\vCard;
use OpenSkos2\Set;
use OpenSkos2\Rdf\Literal;
use Rhumsaa\Uuid\Uuid;

require 'autoload.inc.php';
require 'Zend/Console/Getopt.php';
require_once 'utils_functions.php';

$opts = array(
  'help|?' => 'Print this usage message',
  'tenant=s' => 'tenants code',
  'key=s' => 'Api key for the Admin account',
  'code=s' => 'sets code',
  'title=s' => 'set title',
  'oaibaseuri=s' => 'OAI base URI',
  'conceptbaseuri=s' => 'Concept base Uri',
  'allowoai=s' => 'allow oai',
  'webpage=s' => 'web page',
  'license=s' => 'license',
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

$must_params = ['tenant', 'key', 'code', 'title', 'oaibaseuri', 'conceptbaseuri', 'allowoai', 'webpage', 'license', 'uuid', 'uri'];
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

    //create set
    insert_set($OPTS->tenant, $resourceManager, $OPTS->uri, $OPTS->uuid, $OPTS->code, $OPTS->title, $OPTS->lang, $OPTS->license, $OPTS->description, $OPTS->conceptbaseuri, $OPTS->oaibaseuri, $OPTS->allowoai, $OPTS->webpage);
    fwrite(STDOUT, 'A set has been created in the triple store with this uri: ' . $OPTS->uri . "\n");
    fwrite(STDOUT, 'To check: try GET <baseuri>/api/set?id=' . $OPTS->uri . "\n");

    break;
  default:
    fwrite(STDERR, "unkown action `{$action}`\n");
    exit(1);
}

exit(0);

// php set.php --tenant=example --key=xxx --code=set01 --title=testset01 --license=http://creativecommons.org/licenses/by/4.0/ --oaibaseuri=http://set01  --allowoai=true --conceptbaseuri=http://set01/set01abc --uuid=set01abc --uri=https://set01/set01abc --webpage=http://set01/page create  
   