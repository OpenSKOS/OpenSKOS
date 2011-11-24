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

class OpenSKOS_Controller_Plugin_Locale extends Zend_Controller_Plugin_Abstract
{
	public function preDispatch(Zend_Controller_Request_Abstract $request)
	{
		if(null !== ($locale = $request->getParam('locale'))) {
			if (strtoupper($locale) === 'NULL') {
				setcookie('openskos_locale', '', time()- 3600, '/');
				$this->_redirect();
			} else {
				try {
					$newLocale = Zend_Locale::findLocale($locale);
				} catch (Zend_Locale_Exception $e) {
					$this->_redirect();
				}
				$this->_setLocale($newLocale);
				setcookie('openskos_locale', $locale, time()+60*60*24*30, '/');
				
				$this->_redirect();
			}
		} elseif (isset($_COOKIE['openskos_locale']) && ($locale = $_COOKIE['openskos_locale'])) {
			try {
				$newLocale = Zend_Locale::findLocale($locale);
			} catch (Zend_Locale_Exception $e) {
				setcookie('openskos_locale', '', time()- 3600, '/');
				return;
			}
			$this->_setLocale($newLocale);
		}
		
	}
	
	protected function _setLocale($newLocale = null)
	{
		Zend_Registry::get('Zend_Locale')->setLocale($newLocale);
		Zend_Registry::get('Zend_Translate')->getAdapter()->setLocale($newLocale);
	}
	
	protected function _redirect()
	{
		$params = $this->getRequest()->getParams();
		unset($params['locale']);
		
		$router = Zend_Controller_Front::getInstance()->getRouter();
        $url = $router->assemble($params, null, true);
        $this->getResponse()
			->setRedirect($url)
			->sendHeaders();
		exit;
	}
}