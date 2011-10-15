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
	
    /**
     * Classname for rowset
     *
     * @var string
     */
    protected $_rowsetClass = 'OpenSKOS_Db_Table_Rowset_Collection';

	protected $_referenceMap = array (
		'Tenant' => array (
			'columns' => 'tenant', 
			'refTableClass' => 'OpenSKOS_Db_Table_Tenants', 
			'refColumns' => 'code'
		)
	);
	
	public static $licences = array(
		'Open Database License (ODbL) v1.0' => 'http://opendatacommons.org/licenses/odbl/1.0/',
		'Attribution (CC BY)' => 'http://creativecommons.org/licenses/by/3.0/legalcode',
		'Attribution Share Alike (CC BY-SA)' => 'http://creativecommons.org/licenses/by-sa/3.0/legalcode',
		'Attribution No Derivatives (CC BY-ND)' => 'http://creativecommons.org/licenses/by-nd/3.0/legalcode',
		'Attribution Non-Commercial (CC BY-NC)' => 'http://creativecommons.org/licenses/by-nc/3.0/legalcode',
		'Attribution Non-Commercial Share Alike (CC BY-NC-SA)' => 'http://creativecommons.org/licenses/by-nc-sa/3.0/legalcode',
		'Attribution Non-Commercial No Derivatives (CC BY-NC-ND)' => 'http://creativecommons.org/licenses/by-nc-nd/3.0/legalcode'
	);
	
	protected $_dependentTables = array('OpenSKOS_Db_Table_CollectionHasNamespaces');
	
	public function findByCode($code, $tenant = null) {
		$select = $this->select ()->where ( 'code=?', $code );
		if (null === $tenant)
			$select->where ( 'tenant=?', is_a($tenant, 'OpenSKOS_Db_Table_Row_Tenant') ? $tenant->code : $tenant );
		return $this->fetchRow ( $select );
	}
	
	public function getClasses(OpenSKOS_Db_Table_Row_Tenant $tenant, OpenSKOS_Db_Table_Row_Collection $collection = null)
	{
		$solr = OpenSKOS_Solr::getInstance();
		$q = 'tenant:'.$tenant->code;
		if (null !== $collection) {
			$q .= ' AND collection:' . $collection->id;
		}
		$result = $solr->search($q, array(
			'rows' => 0,
			'facet' => 'true',
			'facet.field' => 'class'
		));
		$classes = array();
		return $result['facet_counts']['facet_fields']['class'];
	}
	
	public function uniqueCode($code, Array $data)
	{
		//fetch the tenant:
		$tenant = OpenSKOS_Db_Table_Tenants::fromIdentity();
		if (null === $tenant) {
			throw new Zend_Db_Table_Exception('This method needs a valid tenant from the Zend_Auth object');
		}
		$select = $this->select()
			->where('tenant=?', $tenant->code)
			->where('code=?', $code);
		if (isset($data['id'])) {
			$select->where('NOT(id=?)', $data['id']);
		}
		return count($this->fetchAll($select)) === 0;
	}
	
}

class OpenSKOS_Db_Table_Rowset_Collection extends Zend_Db_Table_Rowset
{
	public function toRdf()
	{
		$doc = OpenSKOS_Db_Table_Row_Collection::getRdfDocument();
		foreach($this as $collection) {
			$doc->documentElement->appendChild($doc->importNode($collection->toRdf()->getElementsByTagname('rdf:Description')->item(0), true));
		}
		return $doc;
	}
}