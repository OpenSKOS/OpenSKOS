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

class OpenSKOS_Controller_Plugin_Autoload extends Zend_Controller_Plugin_Abstract
{
	public function preDispatch(Zend_Controller_Request_Abstract $request)
	{
		//make sure we have an Autoloader for all models in all modules:
		$options = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getOption('resources');
		$modules = array_keys($options['frontController']['controllerDirectory']);
		
		foreach ($modules as $module) {
			set_include_path(implode(PATH_SEPARATOR, array(
				APPLICATION_PATH . '/' . $module,
				get_include_path() 
			)));
			$parts = explode('-', $module);
			array_walk($parts, create_function('&$v', '$v=ucfirst($v);'));
			
			$namespacePrefix = implode('', $parts).'_';
			$loader = new OpenSKOS_Autoloader();
			
			Zend_Loader_Autoloader::getInstance()
				->pushAutoloader($loader, $namespacePrefix);
		}
	}
}