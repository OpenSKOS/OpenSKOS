<?php

class OpenSKOS_Db_Table_Tenants extends Zend_Db_Table
{
	protected $_name = 'tenant';
	protected $_sequence = false;
    
    /**
     * Classname for row
     *
     * @var string
     */
    protected $_rowClass = 'OpenSKOS_Db_Table_Row_Tenant';
    
    protected $_dependentTables = array('OpenSKOS_Db_Table_Collections');

    public static function fromIdentity()
    {
    	if (!Zend_Auth::getInstance()->hasIdentity()) {
    		return;
    	}
    	$identity = Zend_Auth::getInstance()->getIdentity();
    	$className = __CLASS__;
    	$model = new $className();
    	return $model->find($identity->tenant)->current();
    }
}