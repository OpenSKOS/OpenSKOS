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
		$params = self::getParams($parameters);
		return $params && isset($params[$key]) ? $params[$key] : null;
	}

	public static function getParams($parameters)
	{
		return unserialize($parameters);
	}
}
