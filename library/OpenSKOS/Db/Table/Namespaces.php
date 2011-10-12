<?php

class OpenSKOS_Db_Table_Namespaces extends Zend_Db_Table
{
	protected $_name = 'namespace';
    
    /**
     * Classname for row
     *
     * @var string
     */
    protected $_rowClass = 'OpenSKOS_Db_Table_Row_Namespace';

	protected $_dependentTables = array('OpenSKOS_Db_Table_CollectionHasNamespaces');
	
    /**
     * Fetches all rows as key value pairs.
     *
     * @param string|array|Zend_Db_Table_Select $where  OPTIONAL An SQL WHERE clause or Zend_Db_Table_Select object.
     * @param string|array                      $order  OPTIONAL An SQL ORDER clause.
     * @param int                               $count  OPTIONAL An SQL LIMIT count.
     * @param int                               $offset OPTIONAL An SQL LIMIT offset.
     * @return associative array prefix => uri
     */
    public function fetchPairs($where = null, $order = null, $count = null, $offset = null)
	{
		$rows = $this->fetchAll($where, $order, $count, $offset);
		$namespaces = array();
		foreach ($rows as $row) {
			$namespaces[$row->prefix] = $row->uri;
		}
		return $namespaces;
	}
	
}