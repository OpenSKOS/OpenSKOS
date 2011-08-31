<?php

class OpenSKOS_Controller_Plugin_Auth extends Zend_Controller_Plugin_Abstract
{
	protected $modules = array('dashboard');
	
	public function preDispatch(Zend_Controller_Request_Abstract $request)
	{
		if (!in_array($request->getModuleName(), $this->modules)) return;
        $authInstance = Zend_Auth::getInstance();
        $resource = $request->getControllerName();
        $actionName = $request->getActionName();
        if ($authInstance->hasIdentity()) {
        } else {
        	$role = 'gast';
        	if ($request->getControllerName()!='login') {
	        	$request->setModuleName('dashboard')
		              ->setControllerName('login')
		              ->setActionName('index');
        	}
        }
	}
}