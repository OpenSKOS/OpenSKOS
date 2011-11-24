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

require_once 'Zend/Loader/PluginLoader.php';
require_once 'Zend/Controller/Action/Helper/Abstract.php';

/**
 * Translate Action Helper 
 * 
 * @uses actionHelper OpenSKOS_Controller_Action_Helper
 */
class OpenSKOS_Controller_Action_Helper_Translate extends Zend_Controller_Action_Helper_Abstract {

	/**
	 * Strategy pattern: call helper as broker method
	 */
	public function translate($msgid) {
		try {
			return Zend_Registry::get('Zend_Translates')->translate($msgid);
		} catch (Zend_Exception $e) {
			return $msgid;
		}
	}
}

