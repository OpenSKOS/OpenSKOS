<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
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
	
	public function _initAuth()
	{
//	    Zend_Controller_Front::getInstance()->registerPlugin(new Application_Plugin_Auth($acl));
	}
}

