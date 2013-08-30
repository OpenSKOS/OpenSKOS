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
 * @copyright  Copyright (c) 2012 Pictura Database Publishing. (http://www.pictura-dp.nl)
 * @author     Alexandar Mitsev
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 */

/**
 * Provides a shortcut for getting the application configuration options.
 * 
 * @author a_mitsev
 */
class OpenSKOS_Application_BootstrapAccess
{
	/**
	 * Gets configuration option from bootstrap.
	 * 
	 * Tries to get the bootstrap from Zend_Controller_Front::getInstance()->getParam('bootstrap').
	 * If there is no bootstrap there gets it from the global variable $application ($application->getBootstrap()).
	 * 
	 * @param string $key
	 * @return mixed
	 * @throws Zend_Exception 
	 */
	public static function getOption($key)
	{
		$bootstrap = self::getBootstrap();
		
		if ($bootstrap->hasOption($key)) {
			return $bootstrap->getOption($key);
		} else {
			throw new Zend_Exception('Getting configuration option failed. Option "' . $key . '" not found.');
		}
	}
	
	/**
	 * Tries to get the bootstrap from Zend_Controller_Front::getInstance()->getParam('bootstrap').
	 * If there is no bootstrap there gets it from the global variable $application ($application->getBootstrap()).
	 *
	 * @return Zend_Application_Bootstrap_BootstrapAbstract
	 * @throws Zend_Exception 
	 */
	public static function getBootstrap()
	{
		$bootstrap = Zend_Controller_Front::getInstance()->getParam('bootstrap');
		if (null === $bootstrap) {
			global $application;
			if (null !== $application && null !== $application->getBootstrap()) {
				$bootstrap = $application->getBootstrap();
			} else {
				throw new Zend_Exception('Bootstrap not found.');
			}
		}
		return $bootstrap;
	}
}