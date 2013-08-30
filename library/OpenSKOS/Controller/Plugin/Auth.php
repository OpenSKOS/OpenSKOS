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

class OpenSKOS_Controller_Plugin_Auth extends Zend_Controller_Plugin_Abstract
{
	protected $modules = array('editor');
	
	public function preDispatch(Zend_Controller_Request_Abstract $request)
	{
		if (!in_array($request->getModuleName(), $this->modules)) return;

		$authInstance = Zend_Auth::getInstance();
		
		//SAML login:
		if (isset($_SERVER['eppn'])) {
		    //lookup user with this eduPersonPrincipalName:
		    $model = new OpenSKOS_Db_Table_Users();
		    $user = $model->fetchRow($model->select()->where('eppn=?', $_SERVER['eppn']));
		    if (null!==$user) {
            	if($user->active != 'Y') {
    				Zend_Auth::getInstance()->clearIdentity();
    				Zend_Session::forgetMe();
    				Zend_Controller_Action_HelperBroker::getStaticHelper('FlashMessenger')->setNamespace('error')->addMessage(_('Your account is blocked.'));
            		Zend_Controller_Action_HelperBroker::getStaticHelper('redirector')->direct('index', 'index', 'website');
            	}
		        $login = new Editor_Models_Login ();
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
        	}
        } else {
        	if ($request->getControllerName()!='login') {
	        	Zend_Controller_Action_HelperBroker::getStaticHelper('redirector')->direct('index', 'login', 'editor');
        	}
        }
	}
}