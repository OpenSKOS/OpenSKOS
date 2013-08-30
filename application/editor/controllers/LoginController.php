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

class Editor_LoginController extends Zend_Controller_Action {
	
	public function init() {
		if (Zend_Auth::getInstance ()->hasIdentity ()) {
			$this->getHelper ( 'FlashMessenger' )->addMessage (_('You are already logged in'));
			$this->_helper->redirector ( 'index', 'index' );
		}
	}
	
	public function indexAction() {
		$this->view->form = Editor_Forms_Login::getInstance ()
			->setAction ( $this->getFrontController ()->getRouter ()->assemble ( array ('module'=>'editor', 'controller'=>'login', 'action' => 'authenticate' ) ) );
	}
	
	public function authenticateAction() {
		$form = Editor_Forms_Login::getInstance ();
		
		$request = $this->getRequest ();
		if (! $this->getRequest ()->isPost ()) {
			$this->_helper->redirector ( 'index' );
		}
		
		if (! $form->isValid ( $this->getRequest ()->getPost () )) {
			return $this->_forward ( 'index' );
		}
		
		$tenant = $form->getValue ( 'tenant' );
		$username = $form->getValue ( 'username' );
		$password = $form->getValue ( 'password' );
		$login = new Editor_Models_Login ();
		$login->setData ($tenant, $username, $password );
		if ($login->isValid ()) {
			
			$session = new Zend_Session_Namespace('Zend_Auth');
            // Set the time of user logged in
            $session->setExpirationSeconds(30*24*3600);
            
            // If "remember" was marked
            if ((int)$form->getValue ('rememberme')) {
                Zend_Session::rememberMe();
            }
            
            // Clears user specific options which are kept in the session if a new login is made.
            $userOptions = new Zend_Session_Namespace('userOptions');
            $userOptions->unsetAll();
            
            $this->getHelper ( 'FlashMessenger' )->addMessage (_('Succesfully logged in'));
			$this->_helper->redirector ( 'index', 'index' );
		} else {
    		$this->getHelper('FlashMessenger')->setNamespace('error')->addMessage(array_pop($login->getMessages()));
    		$this->_helper->redirector('index');
		}
	}
	
	/**
	 * Starts an OpenID detection and login process.
	 *
	 */
	public function openidLoginAction() {
		
		$form = Editor_Forms_OpenIdLogin::getInstance();
		if ( ! $form->isValid($this->getRequest()->getParams())) {
			return $this->_forward('index');
		}
		
		Zend_Loader::loadClass('LightOpenId_Consumer');
		$serverUrl = new Zend_View_Helper_ServerUrl();
		
		$consumer = new LightOpenId_Consumer($serverUrl->getHost());
		$consumer->identity = $this->getRequest()->getParam('openIdIdentity');
		$consumer->returnUrl = $serverUrl->serverUrl() . $this->getHelper('url')->url(array('module' => 'editor', 'controller' => 'login', 'action' => 'openid-callback', 'rememberme' => $this->getRequest()->getParam('rememberme', 0)), 'default', true);
		$consumer->required = array('contact/email');
		
		$this->_redirect($consumer->authUrl());
	}
	
	/**
	 * When the OpenID login is ready it redirects the user to this page.
	 * Here happens the authentication of the user if he logs in with OpenID.
	 * 
	 */
	public function openidCallbackAction() {
			
		Zend_Loader::loadClass('LightOpenId_Consumer');
		$serverUrl = new Zend_View_Helper_ServerUrl();
		$consumer = new LightOpenId_Consumer($serverUrl->getHost());
		
		if ($consumer->validate()) {
			
			$userData = $consumer->getAttributes();
			
			if (isset($userData['contact/email']) && ! empty($userData['contact/email'])) {
				
				// Loads the user by its email retrieved from the OpenID provider.
				$login = new Editor_Models_OpenIdLogin();
				$login->setData($userData['contact/email']);
				if ($login->isValid()) {
					$session = new Zend_Session_Namespace('Zend_Auth');
					// Set the time of user logged in
					$session->setExpirationSeconds(30*24*3600);
					
					// If "remember me" was marked
					if ((int)$this->getRequest()->getParam('rememberme')) {
						Zend_Session::rememberMe();
					}
					
					// Clears user specific options which are kept in the session if a new login is made.
					$userOptions = new Zend_Session_Namespace('userOptions');
					$userOptions->unsetAll();
					
					$this->getHelper('FlashMessenger')->addMessage(_('Succesfully logged in'));
					$this->_helper->redirector('index', 'index');
				} else {
					$this->getHelper('FlashMessenger')->setNamespace('error')->addMessage(_('You have succesfully logged in with your Google account, but no user was found in our system with the emailaddress') . ' "' . $userData['email'] . '". ' . _('Please contact your application manager to give you access to OpenSKOS.'));
					$this->_helper->redirector('index');
				}
				
			} else {
				$this->getHelper('FlashMessenger')->setNamespace('error')->addMessage('We couldn\'t retrieve your email address from google.');
				$this->_helper->redirector('index');
			}
			
		} else {
			$this->getHelper('FlashMessenger')->setNamespace('error')->addMessage(_('Unable to verify OpenID identity') . '("' . $openIdIdentity . '"). ' . _('Error:') . ' "' . $consumer->getError() . '".');
    		$this->_helper->redirector('index');
		}
	}
}