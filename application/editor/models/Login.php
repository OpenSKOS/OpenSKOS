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

class Editor_Models_Login extends Zend_Auth {

    protected $_authAdapter, $_auth_result = array();

    public function __construct() {
        $this->_authAdapter = new Editor_Models_Login_AuthAdapter();
        $this->_authAdapter->setTableName('user')
        	->setTenantColumn('tenant')
        	->setIdentityColumn('email')
        	->setCredentialColumn('password');
    }

    public function setData($tenant, $username, $password) {
        $this->_authAdapter
        	->setTenant($tenant)
        	->setIdentity($username)
        	->setCredential(md5($password));
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

class Editor_Models_Login_AuthAdapter extends Zend_Auth_Adapter_DbTable
{
    /**
     * $_itenantColumn - the column to use as the tenant
     *
     * @var string
     */
    protected $_tenantColumn = null;

    /**
     * $_tenant - Tenant value
     *
     * @var string
     */
    protected $_tenant = null;

    public function __construct()
	{
		parent::__construct(Zend_Db_Table::getDefaultAdapter());
	}
	
    /**
     * setTenantColumn() - set the column name to be used as the identity column
     *
     * @param  string $tenantColumn
     * @return Zend_Auth_Adapter_DbTable Provides a fluent interface
     */
    public function setTenantColumn($tenantColumn)
    {
        $this->_tenantColumn = $tenantColumn;
        return $this;
    }

    /**
     * setTenant() - set the value to be used as the tenant
     *
     * @param  string $value
     * @return Zend_Auth_Adapter_DbTable Provides a fluent interface
     */
    public function setTenant($value)
    {
        $this->_tenant = $value;
        return $this;
    }

    /**
     * _authenticateCreateSelect() - This method creates a Zend_Db_Select object that
     * is completely configured to be queried against the database.
     *
     * @return Zend_Db_Select
     */
    protected function _authenticateCreateSelect()
    {
    	$select = parent::_authenticateCreateSelect()
    		->where($this->_zendDb->quoteIdentifier($this->_tenantColumn, true) . ' = ?', $this->_tenant);
    	return $select;
    }
}