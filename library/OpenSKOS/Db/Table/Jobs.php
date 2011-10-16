<?php

class OpenSKOS_Db_Table_Jobs extends Zend_Db_Table 
{
	protected $_name = 'job';
	
	/**
	 * Classname for row
	 *
	 * @var string
	 */
	protected $_rowClass = 'OpenSKOS_Db_Table_Row_Job';
	
	protected $_referenceMap = array (
		'Collections' => array (
			'columns' => 'collection', 
			'refTableClass' => 'OpenSKOS_Db_Table_Collections', 
			'refColumns' => 'id'
		),
		'Users' => array (
			'columns' => 'user', 
			'refTableClass' => 'OpenSKOS_Db_Table_Users', 
			'refColumns' => 'id'
		)
	);

	public static function getParam($parameters, $key)
	{
		$params = unserialize($parameters);
		return $params && isset($params[$key]) ? $params[$key] : null;
	}
}
