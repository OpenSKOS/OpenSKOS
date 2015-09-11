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

class EchoLogger extends \Psr\Log\AbstractLogger
{
    public function log($level, $message, array $context = array())
    {
        echo $message . PHP_EOL;
    }
}

// Test....

/* @var $diContainer DI\Container */
$diContainer = Zend_Controller_Front::getInstance()->getDispatcher()->getContainer();

/* @var $resourceManager OpenSkos2\Rdf\ResourceManager */
$resourceManager = $diContainer->get('OpenSkos2\Rdf\ResourceManager');

//$file = new OpenSkos2\File('/home/www/temp/concept.xml');
//$validator = new \OpenSkos2\Validator\Validator(
//    $resourceManager,
//    new \OpenSkos2\Tenant('pic', true)
//);
//$validator->validateCollection($file->getResources(), new EchoLogger());

use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Rdf\Literal;
use OpenSkos2\Rdf\Uri;

$message = new OpenSkos2\Export\Message(
    'xml',
    [Skos::INSCHEME => new Uri('http://AM/collection1/Dogs')],
    [],
    1
);

$command = new OpenSkos2\Export\Command($resourceManager);

echo $command->handle($message);

//
