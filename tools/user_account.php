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
 * @copyright  Copyright (c) 2011 Pictura Database Publishing. (http://www.pictura-dp.nl)
 * @author     Mark Lindeman
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 */
/* VOORBEELD!!!!
 * Run the file as :  php user_account.php --adminkey=xxx --code=example --name=user2  --email=name@mail.com --password=yyy --apikey=zzz --role=guest  create
 */

use DI\Container;

require 'autoload.inc.php';
require 'Zend/Console/Getopt.php';

$opts = array(
  'help|?' => 'Print this usage message',
  'env|e=s' => 'The environment to use (defaults to "production")',
  'code=s' => 'Tenants (institution) code (required)',
  'adminkey=s' => 'The key of the administrator of this tenant (required)',
  'name=s' => 'New-user name (required)',
  'eppn=s' => 'New-user eppn (required)',
  'email=s' => 'New-user e-mail (required)',
  'password=s' => 'Password for the new user account (required)',
  'apikey=s' => 'Api key for the new user account (required)',
  'role=s' => 'New user role (default: guest)'
);

$OPTS = new Zend_Console_Getopt($opts);

if ($OPTS->help) {
  echo str_replace('[ options ]', '[ options ] action', $OPTS->getUsageMessage());
  exit(0);
}


$args = $OPTS->getRemainingArgs();
$action = $args[count($args)-1];


require dirname(__FILE__) . '/bootstrap.inc.php';

/* @var $diContainer Container */
$diContainer = Zend_Controller_Front::getInstance()->getDispatcher()->getContainer();

/**
 * @var $resourceManager \OpenSkos2\Rdf\ResourceManager
 */
$resourceManager = $diContainer->make('\OpenSkos2\Rdf\ResourceManager');
$model = new OpenSKOS_Db_Table_Users();
        

if (null === $OPTS->code) {
  fwrite(STDERR, "missing required `code` argument\n");
  exit(1);
}

if (null === $OPTS->adminkey) {
  fwrite(STDERR, "missing required `adminkey` argument\n");
  exit(1);
} else {
  $admin = $resourceManager->fetchRowWithRetries($model, 'apikey = ' . $model->getAdapter()->quote($OPTS->adminkey) . ' '
    . 'AND tenant = ' . $model->getAdapter()->quote($OPTS->code)
  );
  if (null === $admin) {
    fwrite(STDERR, 'There is no user with the key ' . $OPTS->adminkey . ' in the tenant with the code '.$OPTS->code."\n");
     exit(1);
  }
  if ($admin->role !== OpenSKOS_Db_Table_Users::USER_ROLE_ADMINISTRATOR) {
    fwrite(STDERR, "The user with the key ". $OPTS->adminkey . ' is not the administrator of the tenant with the code '.$OPTS->code."\n");
     exit(1);
  }
}

function user_with_parameter_exists($parametername, $parametervalue, $tenantcode, $model, $resManager){
   $rows = $resManager->fetchRowWithRetries($model, $parametername .' = ' . $model->getAdapter()->quote($parametervalue) . ' '
    . 'AND tenant = ' . $model->getAdapter()->quote($tenantcode)
  );
  if (null !== $rows) {
    fwrite(STDERR, 'There are already registered users withe the '. $parametername  .' set to '. $parametervalue . ' in the tenant with the code '.$tenantcode. "\n");
    exit(1);
  }
};


if (null === $OPTS->name) {
  fwrite(STDERR, "missing required `name` argument\n");
  exit(1);
} else {
  user_with_parameter_exists('name', $OPTS->name, $OPTS->code, $model, $resourceManager);
}

if (null === $OPTS->eppn) {
  fwrite(STDERR, "missing required `eppn` argument\n");
  exit(1);
} else {
  user_with_parameter_exists('eppn', $OPTS->eppn, $OPTS->code, $model, $resourceManager);
}

if (null === $OPTS->email) {
  fwrite(STDERR, "missing required `email` argument\n");
  exit(1);
} else {
  user_with_parameter_exists('email', $OPTS->email, $OPTS->code, $model, $resourceManager);
}

if (null === $OPTS->password) {
  fwrite(STDERR, "missing required `password` argument\n");
  exit(1);
} else {
  user_with_parameter_exists('password', $OPTS->password, $OPTS->code, $model, $resourceManager);
}

if (null === $OPTS->apikey) {
  fwrite(STDERR, "missing required `apikey` argument\n");
  exit(1);
} else {
  user_with_parameter_exists('apikey', $OPTS->apikey, $OPTS->code, $model, $resourceManager);
}

fwrite(STDOUT, "\n\n\n Strating add-user script \n ");
switch ($action) {
  case 'create':
  var_dump($OPTS->eppn);
    // create user
    $model->createRow(array(
      'email' => $OPTS->email,
      'name' => $OPTS->name,
      'password' => new Zend_Db_Expr('MD5(' . $model->getAdapter()->quote($OPTS->password) . ')'),
      'tenant' => $OPTS->code,
      'eppn' => $OPTS->eppn,
      'apikey' => $OPTS->apikey,
      'type' => OpenSKOS_Db_Table_Users::USER_TYPE_API,
      'role' => $OPTS->role,
    ))->save();

    //add  user-info to triple store
    //first get it from MySql 
    $user = $resourceManager->fetchRowWithRetries($model, 'apikey = ' . $model->getAdapter()->quote($OPTS->apikey) . ' '
      . 'AND tenant = ' . $model->getAdapter()->quote($OPTS->code)
    );
    // second, getFoafPersonMethod adds a user automatically to the triple tore
    $useruri = $user->getFoafPerson()->getUri();

    fwrite(STDOUT, 'A tenant has been created with this user account:' . "\n");
    fwrite(STDOUT, "  - new user name: {$OPTS->name}\n");
    fwrite(STDOUT, "  - login: {$OPTS->email}\n");
    fwrite(STDOUT, "  - password: {$OPTS->password}\n");
    fwrite(STDOUT, "  - apikey: {$OPTS->apikey}\n");
    fwrite(STDOUT, "  - user uri: {$useruri}\n");
    break;
  default:
    fwrite(STDERR, "unkown action `{$action}`\n");
    exit(1);
}

exit(0);
