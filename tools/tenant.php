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
use DI\Container;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Rdf\Literal;
use OpenSkos2\Tenant;

require_once 'autoload.inc.php';
require_once 'Zend/Console/Getopt.php';


$opts = array(
    'help|?' => 'Print this usage message',
    'env|e=s' => 'The environment to use (defaults to "production")',
    'epic=s' => 'Epic is enabled or not, true/false',
    'uri=s' => 'tenant uri',
    'uuid=s' => 'tenant uuid',
    'code=s' => 'Tenant code (required)',
    'name=s' => 'Tenant name (required when creating a tenant)',
    'disableSearchInOtherTenants=s' => 'disable Search In Other Tenants',
    'enableStatussesSystem=s' => 'enable Statusses System',
    'email=s' => 'Admin email (required when creating a tenant)',
    'password=s' => 'Password for the Admin account',
    'apikey=s' => 'Api key for the Admin account',
    'eppn=s' => 'eppn for the admin',
    'enableSkosXl' => 'enable skos xl labels'
);
$OPTS = new Zend_Console_Getopt($opts);

if ($OPTS->help) {
    echo str_replace('[ options ]', '[ options ] action', 
        $OPTS->getUsageMessage());
    exit(0);
}

$args = $OPTS->getRemainingArgs();

if (!$args || count($args) != 1) {
    echo str_replace('[ options ]', '[ options ] action', 
        $OPTS->getUsageMessage());
    fwrite(STDERR, "Expected an action (create|delete)\n");
    exit(1);
}
$action = $args[0];

$query = $OPTS->query;

if (null === $OPTS->code) {
    fwrite(STDERR, "missing required `code` argument\n");
    exit(1);
}

if (null === $OPTS->apikey) {
    fwrite(STDERR, "missing required `apikey` argument\n");
    exit(1);
}

require_once dirname(__FILE__) . '/bootstrap.inc.php';
require_once 'utils_functions.php';

/* @var $diContainer Container */
$diContainer = Zend_Controller_Front::getInstance()->getDispatcher()->getContainer();

/**
 * @var $resourceManager \OpenSkos2\Rdf\ResourceManager
 */
$resourceManager = $diContainer->make('\OpenSkos2\Rdf\ResourceManager');


fwrite(STDOUT, "\n\n\n Starting script tenant... \n ");
switch ($action) {
    case 'create':

        //create tenant 
        $tenantRdf = createTenantRdf($OPTS->code, 
            $OPTS->name, 
            $OPTS->epic, 
            $OPTS->uri, 
            $OPTS->uuid, 
            $OPTS->disableSearchInOtherTenants, 
            $OPTS->enableStatussesSystem, 
            $OPTS->enableSkosXl, 
            $resourceManager);
        $resourceManager->insert($tenantRdf);
        fwrite(STDOUT, 'A tenant has been created in the triple store with this uri: ' . 
            $tenantRdf->getUri() . "\n");
        fwrite(STDOUT, 'To check: try GET <host>/api/institution?id=' . 
            $tenantRdf->getUri() . "\n");
        fwrite(STDOUT, "Now Im about to add the user in "
            . "the MySQL database ... \n\n");

        // create user
        $model = new OpenSKOS_Db_Table_Users();
        $model->createRow(array(
            'email' => $OPTS->email,
            'name' => $OPTS->name,
            'password' => new Zend_Db_Expr('MD5(' . $model->getAdapter()->quote($OPTS->password) . ')'),
            'tenant' => $OPTS->code,
            'apikey' => $OPTS->apikey,
            'eppn' => $OPTS->eppn,
            'type' => OpenSKOS_Db_Table_Users::USER_TYPE_BOTH,
            'role' => OpenSKOS_Db_Table_Users::USER_ROLE_ADMINISTRATOR,
        ))->save();

        // add  user-info to triple store
        //firsts get it from MySql 
        $user = $resourceManager->fetchRowWithRetries($model, 'apikey = ' . $model->getAdapter()->quote($OPTS->apikey) . ' '
            . 'AND tenant = ' . $model->getAdapter()->quote($OPTS->code)
        );
        // second, getFoafPersonMethod adds a user automatically to the triple tore
        $useruri = $user->getFoafPerson()->getUri();

        fwrite(STDOUT, 'A tenant has been created with this user account:' . "\n");
        fwrite(STDOUT, "  - code: {$OPTS->code}\n");
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

// php tenant.php --epic=true --code=testcode8 --name=testtenant8 --disableSearchInOtherTenants=true --enableStatussesSystem=true --email=o4@mail.com --uri=http://ergens/xxx5 --uuid=yyy5 --password=xxx create


