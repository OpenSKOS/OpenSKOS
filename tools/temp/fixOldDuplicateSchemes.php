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


include dirname(__FILE__) . '/../autoload.inc.php';

/* 
 * Updates the status expired to status obsolete
 */

require_once 'Zend/Console/Getopt.php';
$opts = array(
    'env|e=s' => 'The environment to use (defaults to "production")',
);

try {
    $OPTS = new Zend_Console_Getopt($opts);
} catch (Zend_Console_Getopt_Exception $e) {
    fwrite(STDERR, $e->getMessage()."\n");
    echo str_replace('[ options ]', '[ options ] action', $OPTS->getUsageMessage());
    exit(1);
}

include dirname(__FILE__) . '/../bootstrap.inc.php';

// Allow loading of application module classes.
$autoloader = new OpenSKOS_Autoloader();
$mainAutoloader = Zend_Loader_Autoloader::getInstance();
$mainAutoloader->pushAutoloader($autoloader, array('Editor_', 'Api_'));

$diContainer = Zend_Controller_Front::getInstance()->getDispatcher()->getContainer();
/* @var $schemeManager \OpenSkos2\ConceptSchemeManager */
$schemeManager = $diContainer->get('OpenSkos2\ConceptSchemeManager');

$wrongSchemes = [
    'http://data.beeldengeluid.nl/expired/Genre_Filmmuseum' => '8ba979ce-94d4-d9e2-8b07-1c739eefc22d',
    'http://data.beeldengeluid.nl/expired/RVDpersonen_OUD' => '38e06c0f-8019-f702-cc49-0deb8b9fe094',
    'http://data.beeldengeluid.nl/expired/RVDtrefwoorden_OUD' => 'e8f13057-b0b3-9d4e-0cf0-800b31f28ca2',
    'http://data.beeldengeluid.nl/expired/Radiogenre_OUD' => '64f4e7e6-18d8-f3f9-85be-e6ed72d48846',
    'http://data.beeldengeluid.nl/expired/TVgenre_OUD' => 'fb1aa1bc-cff8-c345-9464-84a0f1446c14',
];

foreach ($wrongSchemes as $schemeUri => $correctUuid) {
    $scheme = $schemeManager->fetchByUri($schemeUri);
    $scheme->setProperty(
        \OpenSkos2\Namespaces\OpenSkos::UUID,
        new \OpenSkos2\Rdf\Literal($correctUuid)
    );
    $schemeManager->replace($scheme);
}
