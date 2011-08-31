<?php

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