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
 * Provides easy access to the Zend_Cache_Core object loaded with the options from the configuration.
 * 
 */
class OpenSKOS_Cache
{
	/**
	 * Constant holding reserved name for the general cache.
	 * 
	 * @var string
	 */
	const GENERAL_CACHE = 'general';
	
	/**
	 * Returns instance of the Zend_Cache_Core configured with the options from the application configuration.
	 * 
	 * @param string $name, optional The name of the cache - general by default. This name is used in the configuration.
	 * @return Zend_Cache_Core
	 */
	public static function getCache($name = self::GENERAL_CACHE)
	{
		static $instances = array();
	
		if ( ! isset($instances[$name])) {
			$manager = OpenSKOS_Application_BootstrapAccess::getBootstrap()->getPluginResource('cachemanager')->getCacheManager();
			$instances[$name] = $manager->getCache($name);
		}
	
		return $instances[$name];
	}
}