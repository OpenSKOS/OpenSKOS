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
 * @copyright  Copyright (c) 2012 Pictura Database Publishing. (http://www.pictura-dp.nl)
 * @author     Alexandar Mitsev
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 */

class Editor_Models_OpenIdLogin extends Zend_Auth {

	protected $_authAdapter, $_auth_result = array();

	public function __construct() {
		$this->_authAdapter = new Zend_Auth_Adapter_DbTable();
		$this->_authAdapter->setTableName('user')
		->setIdentityColumn('email')
		->setCredentialColumn('email');
	}

	public function setData($email) {
		$this->_authAdapter
		->setIdentity($email)
		->setCredential($email);
	}

	public function getMessages()
	{
		return null === $this->_auth_result ? array() : $this->_auth_result->getMessages();
	}

	/**
	 * @return Zend_Auth_Result
	 */
	public function isValid() {
		$this->_auth_result = $this->_authAdapter->authenticate();
		if ($this->_auth_result->isValid()) {
			$identify = $this->_authAdapter->getResultRowObject();
			$storage = $this->getStorage();
			$storage->write($identify);
		}
		return $this->_auth_result->isValid();
	}
}