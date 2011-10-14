<?php

class Dashboard_LogoutController extends Zend_Controller_Action
{
	public function indexAction()
	{
		Zend_Auth::getInstance()->clearIdentity();
		Zend_Session::forgetMe();
    	$this->_helper->redirector('index', 'index', 'website');
	}
}