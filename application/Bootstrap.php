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

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
    protected function _initDefaultTimeZone()
    {
        date_default_timezone_set('UTC');
    }
	protected function _initRestRoute()
	{
		$this->bootstrap('frontController');	
		$front = $this->getResource('FrontController');
		$restRoute = new Zend_Rest_Route(
			$front, 
			array(), 
			array(
				'api'
			)
		);
		$front->getRouter()->addRoute('rest', $restRoute);
	}
	
	public function _initActionHelpers()
	{
    	// register the OpenSKOS action helpers
		Zend_Controller_Action_HelperBroker::addPath(APPLICATION_PATH . '/../library/OpenSKOS/Controller/Action/Helper', 'OpenSKOS_Controller_Action_Helper');
	}
	
	public function _initAuth()
	{
//	    Zend_Controller_Front::getInstance()->registerPlugin(new Application_Plugin_Auth($acl));
	}
}

