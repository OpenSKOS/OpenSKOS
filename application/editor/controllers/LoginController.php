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
		$login->setData($tenant, $username, $password );
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
	 * Starts an OAuth2 detection and login process.
	 *
	 */
	public function oauth2LoginAction() {
		$request = $this->getRequest();        
        
		$form = Editor_Forms_OAuthLogin::getInstance();
		if ( ! $form->isValid($request->getParams())) {
			return $this->_forward('index');
		}
        
        $provider = $this->_getOAuth2Provider();
        
        $authorizationUrl = $provider->getAuthorizationUrl();
        
        $oAuth2State = new Zend_Session_Namespace('oAuth2State');
        $oAuth2State->state = $provider->state;
        
		$this->_redirect($authorizationUrl);
	}
	
	/**
	 * When the OAuth2 login is ready it redirects the user to this page.
	 * Here happens the authentication of the user if he logs in with OAuth2.
	 * 
	 */
	public function oauth2CallbackAction() {
        $request = $this->getRequest();
        
        $oAuth2State = new Zend_Session_Namespace('oAuth2State');
        
		if ($oAuth2State->state == $request->getParam('state')) {
			
			$provider = $this->_getOAuth2Provider();
            $token = $provider->getAccessToken('authorization_code', ['code' => $request->getParam('code')]);                        
            $userData = $provider->getUserDetails($token);
            
			if (isset($userData->email) && ! empty($userData->email)) {
				
				// Loads the user by its email retrieved from the OAuth2 provider.
				$login = new Editor_Models_OAuthLogin();
				$login->setData($userData->email);
				if ($login->isValid()) {
					$session = new Zend_Session_Namespace('Zend_Auth');
					// Set the time of user logged in
					$session->setExpirationSeconds(30*24*3600);
					
					// If "remember me" was marked
					if ((int)$request->getParam('rememberme')) {
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
			$this->getHelper('FlashMessenger')->setNamespace('error')->addMessage(_('Unable to verify OAuth state.'));
    		$this->_helper->redirector('index');
		}
	}
    
    /**
     * Gets configured OAuth2 Client Provider.
     * @param string $provider
     * @return \League\OAuth2\Client\Provider\Google
     */
    protected function _getOAuth2Provider() {
        $request = $this->getRequest();
        $serverUrl = new Zend_View_Helper_ServerUrl();
        
        $provider = $request->getParam('provider');        
        $providerClass = '\League\OAuth2\Client\Provider\\' . ucfirst($provider);
        
        // !TODO in config: provider.site...
        return new $providerClass([
            'clientId'      => '281127000043-a3bidfbbjsc5b6nd8gelipl1c3kms3cn.apps.googleusercontent.com',
            'clientSecret'  => 'kJ2hvjpV1D_eCl6LOsYQVSBC',
            'scopes'        => ['email'],
            'redirectUri'   => $serverUrl->serverUrl()
                . $this->getHelper('url')->url(
                    [
                        'module' => 'editor',
                        'controller' => 'login',
                        'action' => 'oauth2-callback',
                        'provider' => $provider,
                        'rememberme' => $request->getParam('rememberme', 0),
                    ],
                    'default',
                    true
                ),
        ]);
    }
}