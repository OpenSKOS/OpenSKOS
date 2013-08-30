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

class OpenSKOS_Autoloader implements Zend_Loader_Autoloader_Interface
{
	public function autoload($class)
	{
		if (preg_match('/^([a-z]+)_(forms|models|plugins)_([a-z]+)(_([a-z]+))?$/i', $class, $match)) {
			
			if (count($match) < 5) {
				list(, $module, $type, $filenameOrFolder) = $match;
				$subFilename = '';
			} else {
				list(, $module, $type, $filenameOrFolder, , $subFilename) = $match;
			}
			
			$path = APPLICATION_PATH 
				. '/' . strtolower($module) 
				. '/' . strtolower($type);
			
			if (empty($subFilename)) {
				$path .= '/' . $filenameOrFolder . '.php';
			} else {
				$path .= '/' . $filenameOrFolder
						 . '/' . $subFilename . '.php';
			} 
				
			if (file_exists($path)) {
				require_once $path;
			}
		}
	}
}