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

class Dashboard_LoginController extends Zend_Controller_Action {
	
	public function init() {
		if (Zend_Auth::getInstance ()->hasIdentity ()) {
			$this->getHelper ( 'FlashMessenger' )->addMessage (_('You are already logged in'));
			$this->_helper->redirector ( 'index', 'index' );
		}
	}
	
	public function indexAction() {
		$this->view->form = Dashboard_Forms_Login::getInstance ()
			->setAction ( $this->getFrontController ()->getRouter ()->assemble ( array ('controller'=>'login', 'action' => 'authenticate' ) ) );
	}
	
	public function authenticateAction() {
		$form = Dashboard_Forms_Login::getInstance ();
		
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
		$login = new Dashboard_Models_Login ();
		$login->setData ($tenant, $username, $password );
		if ($login->isValid ()) {
			
			$session = new Zend_Session_Namespace('Zend_Auth');
            // Set the time of user logged in
            $session->setExpirationSeconds(30*24*3600);
            
            // If "remember" was marked
            if ((int)$form->getValue ('rememberme')) {
                Zend_Session::rememberMe();
            }
            
            
            $this->getHelper ( 'FlashMessenger' )->addMessage (_('Succesfully logged in'));
			$this->_helper->redirector ( 'index', 'index' );
		} else {
    		$this->getHelper('FlashMessenger')->setNamespace('error')->addMessage(array_pop($login->getMessages()));
    		$this->_helper->redirector('index');
		}
	}
}
