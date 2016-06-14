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
 * Run the file as :  php tenant.php --epic=true --code=testcode8 --name=testtenant8 --disableSearchInOtherTenants=true --enableStatussesSystem=true --email=o4@mail.com --uri=http://ergens/xxx5 --uuid=yyy5 --password=xxx create
 */

use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Namespaces\Org;
use OpenSkos2\Namespaces\vCard;
use OpenSkos2\Tenant;
use OpenSkos2\Rdf\Literal;
use Rhumsaa\Uuid\Uuid;

require 'autoload.inc.php';
require 'Zend/Console/Getopt.php';

$opts = array(
    'help|?' => 'Print this usage message',
    'env|e=s' => 'The environment to use (defaults to "production")',
    'epic=s' => 'Epic is enabled or not), true/false',
    'uri=s' => 'tenant uri',
    'uuid=s' => 'tenant uuid',
    'code=s' => 'Tenant code (required)',
    'name=s' => 'Tenant name (required when creating a tenant)',
    'disableSearchInOtherTenants=s' => 'disable Search In Other Tenants', 
    'enableStatussesSystem=s' => 'enable Statusses System',
    'email=s' => 'Admin email (required when creating a tenant)',
    'password=s' => 'Password for the Admin account'
);
$OPTS = new Zend_Console_Getopt($opts);

if ($OPTS->help) {
    echo str_replace('[ options ]', '[ options ] action', $OPTS->getUsageMessage());
    exit(0);
}

$args = $OPTS->getRemainingArgs();

if (!$args || count($args) != 1) {
    echo str_replace('[ options ]', '[ options ] action', $OPTS->getUsageMessage());
    fwrite(STDERR, "Expected an action (create|delete)\n");
    exit(1);
}
$action = $args[0];

$query = $OPTS->query;

if (null === $OPTS->code) {
    fwrite(STDERR, "missing required `code` argument\n");
    exit(1);
}

require dirname(__FILE__) . '/bootstrap.inc.php';

/* @var $diContainer DI\Container */
$diContainer = Zend_Controller_Front::getInstance()->getDispatcher()->getContainer();

/**
 * @var $resourceManager \OpenSkos2\Rdf\ResourceManager
 */
$resourceManager = $diContainer->make('\OpenSkos2\Rdf\ResourceManager');
$resourceManager->setIsNoCommitMode(true);

function setID(&$resource, $uri, $uuid, $resourceManager){
    if ($uri !== null && $uri !== "") {
        $exists = $resourceManager->askForUri($uri);
        if ($exists) {
            fwrite(STDERR, "A institution with the uri " . $uri . " has been already registered in the triple store. \n");
            exit(1);
        }
        if ($uuid !== null && $uuid !== "") {
            $insts = $resourceManager->fetchSubjectWithPropertyGiven(OpenSkos::UUID, '"' . $uuid . '"');
            if (count($insts) > 0) {
                fwrite(STDERR, "A institution with the uuid " . $uuid . " has been already registered in the triple store. \n");
                exit(1);
            } 
            $resource->setUri($uri);
            $resource->setProperty(OpenSkos::UUID, new Literal($uuid));
        } else {
            fwrite(STDERR, "You should provide an uuid as well. \n");
            exit(1);
        }
    } else {
        fwrite(STDERR, "You should provide an uri \n");
        exit(1);
    } 
}

function createTenantRdf($code, $name, $epic, $uri, $uuid, $disableSearchInOtherTenants, $enableStatussesSystem, $resourceManager) {

    $resources = $resourceManager->fetchSubjectWithPropertyGiven(OpenSkos::CODE, '"'.$code.'"', Org::FORMALORG);
    if (count($resources) > 0) {
        fwrite(STDERR, 'A tenant  with the code ' . $code . " has been already registered in the triple store. \n ");
        exit(1);
    }
    
    $insts = $resourceManager->fetchSubjectWithPropertyGiven(vCard::ORGNAME, '"' . $name . '"');
    if (count($insts) > 0) {
        fwrite(STDERR, "A institution with the name " . $name . " has been already registered in the triple store. \n");
        exit(1);
    }

    $tenantResource = new Tenant();
    if ($epic === 'true') {
        try {
        $uri = $tenantResource->selfGenerateUri($code, $resourceManager);
        } catch (Exception $ex) {
           fwrite(STDOUT, "\n Epic failed: " . $ex->getMessage() ." \n");
           fwrite(STDOUT,  "\n I will use the uri and uuid provided by you \n");
           setID($tenantResource, $uri, $uuid, $resourceManager);
        };
    } else {
        setID($tenantResource, $uri, $uuid, $resourceManager);
    }
       

    $tenantResource->setProperty(OpenSkos::CODE, new \OpenSkos2\Rdf\Literal($code));
    $organisation = new \OpenSkos2\Rdf\Resource('node-org-'. Uuid::uuid4());
    $resourceManager->setLiteralWithEmptinessCheck($organisation, vCard::ORGNAME, $name);
    //$resourceManager->setLiteralWithEmptinessCheck($organisation, vCard::ORGUNIT, " ");
    $tenantResource->setProperty(vCard::ORG, $organisation);
    //$resourceManager->setUriWithEmptinessCheck($tenantResource, OpenSkos::WEBPAGE, " ");
    //$resourceManager->setLiteralWithEmptinessCheck($tenantResource, vCard::EMAIL, "");

    $adress = new \OpenSkos2\Rdf\Resource('node-adr-'.Uuid::uuid4());
    //$resourceManager->setLiteralWithEmptinessCheck($adress, vCard::STREET, "");
    //$resourceManager->setLiteralWithEmptinessCheck($adress, vCard::LOCALITY, "");
    //$resourceManager->setLiteralWithEmptinessCheck($adress, vCard::PCODE, "");
    //$resourceManager->setLiteralWithEmptinessCheck($adress, vCard::COUNTRY, "");
    $tenantResource->setProperty(vCard::ADR, $adress);

    $resourceManager->setBooleanLiteralWithEmptinessCheck($tenantResource, OpenSkos::DISABLESEARCHINOTERTENANTS, $disableSearchInOtherTenants);
    $resourceManager->setBooleanLiteralWithEmptinessCheck($tenantResource, OpenSkos::ENABLESTATUSSESSYSTEMS, $enableStatussesSystem);
    return $tenantResource;
}

fwrite(STDOUT, "\n\n\n Strating script ... \n ");
switch ($action) {
    case 'create':
        
        //create tenant 
        $tenantRdf = createTenantRdf($OPTS->code, $OPTS->name, $OPTS->epic, $OPTS->uri, $OPTS->uuid, $OPTS->disableSearchInOtherTenants, $OPTS->enableStatussesSystem, $resourceManager);
        $resourceManager->insert($tenantRdf);
        fwrite(STDOUT, 'A tenant has been created in the triple store with this uri: ' . $tenantRdf->getUri() . "\n");
        fwrite(STDOUT, 'To check: try GET <host>/api/institution?id=' . $tenantRdf->getUri() . "\n");
        fwrite(STDOUT, "Now Im about to add the user in the MySQL database ... \n\n");
        
        // create user
        $model = new OpenSKOS_Db_Table_Users();
        $model->createRow(array(
            'email' => $OPTS->email,
            'name' => $OPTS->name,
            'password' => new Zend_Db_Expr('MD5(' . $model->getAdapter()->quote($OPTS->password) . ')'),
            'tenant' => $OPTS->code,
            'type' => OpenSKOS_Db_Table_Users::USER_TYPE_BOTH,
            'role' => OpenSKOS_Db_Table_Users::USER_ROLE_ADMINISTRATOR,
        ))->save();
        fwrite(STDOUT, 'A tenant has been created with this user account:' . "\n");
        fwrite(STDOUT, "  - code: {$OPTS->code}\n");
        fwrite(STDOUT, "  - login: {$OPTS->email}\n");
        fwrite(STDOUT, "  - password: {$OPTS->password}\n");
        break;
    default:
        fwrite(STDERR, "unkown action `{$action}`\n");
        exit(1);
}

exit(0);
