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
/* @var $resourceManager \OpenSkos2\Rdf\ResourceManager */
$resourceManager = $diContainer->get('OpenSkos2\Rdf\ResourceManager');

/* @var $conceptSchemeManager \OpenSkos2\ConceptSchemeManager */
$conceptSchemeManager = $diContainer->get('OpenSkos2\ConceptSchemeManager');

use OpenSkos2\Namespaces\Rdf;
use OpenSkos2\Rdf\Uri;
use OpenSkos2\ConceptScheme;

$wrongType = 'http://www.w3.org/2004/02/skos/core#conceptScheme';

$count = 0;
foreach ($resourceManager->fetch([Rdf::TYPE => new Uri($wrongType)]) as $conceptScheme) {
    echo $conceptScheme->getUri() . PHP_EOL;
    $conceptScheme->setProperty(Rdf::TYPE, new Uri(ConceptScheme::TYPE));
    $conceptSchemeManager->replace($conceptScheme);
    $count ++;
}

echo $count . ' processed concept schemes.' . PHP_EOL;
