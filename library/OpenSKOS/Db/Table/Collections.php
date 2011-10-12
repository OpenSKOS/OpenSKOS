<?php

class OpenSKOS_Db_Table_Collections extends Zend_Db_Table 
{
	protected $_name = 'collection';
	
	/**
	 * Classname for row
	 *
	 * @var string
	 */
	protected $_rowClass = 'OpenSKOS_Db_Table_Row_Collection';
	
	protected $_referenceMap = array (
		'Tenant' => array (
			'columns' => 'tenant', 
			'refTableClass' => 'OpenSKOS_Db_Table_Tenants', 
			'refColumns' => 'code'
		)
	);
	
	protected $_dependentTables = array('OpenSKOS_Db_Table_CollectionHasNamespaces');
	
	public function findByCode($code, $tenant = null) {
		$select = $this->select ()->where ( 'code=?', $code );
		if (null === $tenant)
			$select->where ( 'tenant=?', $tenant );
		return $this->fetchRow ( $select );
	}
}