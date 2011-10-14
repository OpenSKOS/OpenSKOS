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
    
    /**
     * Classname for rowset
     *
     * @var string
     */
    protected $_rowsetClass = 'OpenSKOS_Db_Table_Rowset_Tenant';

    protected $_dependentTables = array('OpenSKOS_Db_Table_Collections');

    /**
     * @return OpenSKOS_Db_Table_Row_Tenant
     */
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

class OpenSKOS_Db_Table_Rowset_Tenant extends Zend_Db_Table_Rowset
{
	public function toRdf()
	{
		$doc = OpenSKOS_Db_Table_Row_Tenant::getRdfDocument();
		foreach($this as $tenant) {
			$doc->documentElement->appendChild($doc->importNode($tenant->toRdf()->getElementsByTagname('v:Vcard')->item(0), true));
		}
		return $doc;
	}
}