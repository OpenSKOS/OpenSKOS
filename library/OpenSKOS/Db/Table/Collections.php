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
		'Creative Commons Zero (CC0)' => 'http://creativecommons.org/publicdomain/zero/1.0/',
		'Creative Commons Attribution (CC BY)' => 'http://creativecommons.org/licenses/by/3.0/legalcode',
		'Creative Commons Attribution Share Alike (CC BY-SA)' => 'http://creativecommons.org/licenses/by-sa/3.0/legalcode',
//		'Creative Commons Attribution No Derivatives (CC BY-ND)' => 'http://creativecommons.org/licenses/by-nd/3.0/legalcode',
//		'Creative Commons Attribution Non-Commercial (CC BY-NC)' => 'http://creativecommons.org/licenses/by-nc/3.0/legalcode',
//		'Creative Commons Attribution Non-Commercial Share Alike (CC BY-NC-SA)' => 'http://creativecommons.org/licenses/by-nc-sa/3.0/legalcode',
//		'Creative Commons Attribution Non-Commercial No Derivatives (CC BY-NC-ND)' => 'http://creativecommons.org/licenses/by-nc-nd/3.0/legalcode',
		'Open Database License (ODbL) v1.0' => 'http://opendatacommons.org/licenses/odbl/1.0/',
		'Open Data Commons Public Domain Dedication and Licence (PDDL)' => 'http://opendatacommons.org/licenses/pddl/1.0/',
		'Open Data Commons Attribution License (ODC-By) v1.0' => 'http://opendatacommons.org/licenses/by/1.0/'
	);
	
	protected $_dependentTables = array(
		'OpenSKOS_Db_Table_CollectionHasNamespaces', 
		'OpenSKOS_Db_Table_Jobs'
	);
	
	public function findByCode($code, $tenant = null) {
		$select = $this->select ()->where ( 'code=?', $code );
		if (null === $tenant)
			$select->where ( 'tenant=?', is_a($tenant, 'OpenSKOS_Db_Table_Row_Tenant') ? $tenant->code : $tenant );
		return $this->fetchRow ( $select );
	}
	
	public function getClasses(OpenSKOS_Db_Table_Row_Tenant $tenant, OpenSKOS_Db_Table_Row_Collection $collection = null)
	{
		$solr = OpenSKOS_Solr::getInstance();
		$q = 'deleted:false tenant:'.$tenant->code;
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
	
	public function getConceptSchemes(OpenSKOS_Db_Table_Row_Collection $collection = null)
	{
		$solr = OpenSKOS_Solr::getInstance();
		$q = 'class:ConceptScheme collection:' . $collection->id . ' AND tenant:' . $collection->tenant . ' AND deleted:false';
		$result = $solr->search($q, array(
			'rows' => 1000
		));
		return new OpenSKOS_SKOS_ConceptSchemes($result);
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
	
	/**
	 * Gets map with id as key and dc_title as value.
	 * 
	 * @param string $tenant
	 * @return array
	 */
	public function getIdToTitleMap($tenant)
	{
		$collections = $this->fetchAll($this->select()->where('tenant=?', $tenant));
		$collectionsMap = array();
		foreach ($collections as $collection) {
			$collectionsMap[$collection->id] = $collection->dc_title;
		}
		return $collectionsMap;
	}
	
    /**
     * Fetches all SQL result rows as an associative array.
     *
     * The first column is the key, the entire row array is the
     * value.  You should construct the query to be sure that
     * the first column contains unique values, or else
     * rows with duplicate values in the first column will
     * overwrite previous data.
     *
	 * @param string|array|Zend_Db_Table_Select $where  OPTIONAL An SQL WHERE clause or Zend_Db_Table_Select object.
     * @param string|array                      $order  OPTIONAL An SQL ORDER clause.
     * @param int                               $count  OPTIONAL An SQL LIMIT count.
     * @param int                               $offset OPTIONAL An SQL LIMIT offset.
     * @return array
     */
    public function fetchAssoc($where = null, $order = null, $count = null, $offset = null)
    {
        if (!($where instanceof Zend_Db_Table_Select)) {
            $select = $this->select();

            if ($where !== null) {
                $this->_where($select, $where);
            }

            if ($order !== null) {
                $this->_order($select, $order);
            }

            if ($count !== null || $offset !== null) {
                $select->limit($count, $offset);
            }

        } else {
            $select = $where;
        }

        return $this->getAdapter()->fetchAssoc($select);
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