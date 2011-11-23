<?php

class OpenSKOS_Controller_Plugin_Auth extends Zend_Controller_Plugin_Abstract
{
	protected $modules = array('dashboard');
	
	public function preDispatch(Zend_Controller_Request_Abstract $request)
	{
		if (!in_array($request->getModuleName(), $this->modules)) return;

		$authInstance = Zend_Auth::getInstance();
		
		//SAML login:
		if (isset($_SERVER['eppn'])) {
		    //lookup user with this eduPersonPrincipalName:
		    $model = new OpenSKOS_Db_Table_Users();
		    $user = $model->fetchRow($model->select()->where('eppn=?', $_SERVER['eppn'])->orWhere('eppn=?', '*'));
		    if (null!==$user) {
            	if($user->active != 'Y') {
    				Zend_Auth::getInstance()->clearIdentity();
    				Zend_Session::forgetMe();
    				Zend_Controller_Action_HelperBroker::getStaticHelper('FlashMessenger')->setNamespace('error')->addMessage(_('Your account is blocked.'));
            		Zend_Controller_Action_HelperBroker::getStaticHelper('redirector')->direct('index', 'index', 'website');
            	} elseif (!OpenSKOS_Db_Table_Users::isDashboardAllowed($user->type)) {
    				Zend_Auth::getInstance()->clearIdentity();
    				Zend_Session::forgetMe();
    				Zend_Controller_Action_HelperBroker::getStaticHelper('FlashMessenger')->setNamespace('error')->addMessage(_('Your account is not allowed to use the dasboard.'));
            		Zend_Controller_Action_HelperBroker::getStaticHelper('redirector')->direct('index', 'index', 'website');
            	}
		        $login = new Dashboard_Models_Login ();
        		$login->getStorage()->write($user);
        		return;
		    }
		}
		
        $resource = $request->getControllerName();
        $actionName = $request->getActionName();
        if ($authInstance->hasIdentity()) {
        	if($authInstance->getIdentity()->active != 'Y') {
				Zend_Auth::getInstance()->clearIdentity();
				Zend_Session::forgetMe();
				Zend_Controller_Action_HelperBroker::getStaticHelper('FlashMessenger')->setNamespace('error')->addMessage(_('Your account is blocked.'));
        		Zend_Controller_Action_HelperBroker::getStaticHelper('redirector')->direct('index', 'index', 'website');
        	} elseif (!OpenSKOS_Db_Table_Users::isDashboardAllowed($authInstance->getIdentity()->type)) {
				Zend_Auth::getInstance()->clearIdentity();
				Zend_Session::forgetMe();
				Zend_Controller_Action_HelperBroker::getStaticHelper('FlashMessenger')->setNamespace('error')->addMessage(_('Your account is not allowed to use the dasboard.'));
        		Zend_Controller_Action_HelperBroker::getStaticHelper('redirector')->direct('index', 'index', 'website');
        	}
        } else {
        	if ($request->getControllerName()!='login') {
	        	$request->setModuleName('dashboard')
		              ->setControllerName('login')
		              ->setActionName('index');
        	}
        }
	}
}