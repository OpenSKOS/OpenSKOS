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
	
	public static function getNamespacesByCollection()
	{
		$db = self::getDefaultAdapter();
		$select = $db->select()
			->from('collection_has_namespace', array('collection'))
			->join('namespace', 'namespace=prefix');
		$rows = $db->fetchAll($select);
		$namespaces = array();
		foreach ($rows as $row) {
			if (!isset($namespaces[$row['collection']])) {
				$namespaces[$row['collection']] = array();
			}
			$namespaces[$row['collection']][] = array(
				'prefix' => $row['prefix'],
				'uri' => $row['uri']
			);
		}
		return $namespaces;
	}
	
}