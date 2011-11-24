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

class OpenSKOS_Db_Table_Users extends Zend_Db_Table 
{
	const USER_TYPE_DASHBOARD = 'dashboard';
	const USER_TYPE_API = 'api';
	const USER_TYPE_BOTH = 'both';
	
	static $types = array(
		self::USER_TYPE_DASHBOARD,
		self::USER_TYPE_API,
		self::USER_TYPE_BOTH
	);
	
	protected $_name = 'user';
	
	/**
	 * Classname for row
	 *
	 * @var string
	 */
	protected $_rowClass = 'OpenSKOS_Db_Table_Row_User';
	
	protected $_referenceMap = array (
		'Tenant' => array (
			'columns' => 'tenant', 
			'refTableClass' => 'OpenSKOS_Db_Table_Tenants', 
			'refColumns' => 'code'
		)
	);
	
	/**
	 * 
	 * @param string $apikey
	 * @return OpenSKOS_Db_Table_Row_User
	 */
	public static function fetchByApiKey($apikey)
	{
		$classname = __CLASS__;
		$model = new $classname();
		return $model->fetchRow($model->select()->where('apikey=?', $apikey));
	}
	
	public static function isDashboardAllowed($usertype)
	{
		return $usertype == self::USER_TYPE_BOTH || $usertype == self::USER_TYPE_DASHBOARD;
	}
	
	public static function isApiAllowed($usertype)
	{
		return $usertype == self::USER_TYPE_BOTH || $usertype == self::USER_TYPE_API;
	}
	
	protected function _uniqueFieldValue($fieldname, $value, $data)
	{
		//fetch the tenant:
		$tenant = OpenSKOS_Db_Table_Tenants::fromIdentity();
		if (null === $tenant) {
			throw new Zend_Db_Table_Exception('This method needs a valid tenant from the Zend_Auth object');
		}
		$select = $this->select()
			->where('tenant=?', $tenant->code)
			->where($fieldname . '=?', $value);
		if (isset($data['id'])) {
			$select->where('NOT(id=?)', $data['id']);
		}
		return count($this->fetchAll($select)) === 0;
	}
	
	public function uniqueEmail($email, Array $data)
	{
		return $this->_uniqueFieldValue('email', $email, $data);
	}
	
	public function uniqueApiKey($apikey, Array $data)
	{
		return $this->_uniqueFieldValue('apikey', $apikey, $data);
	}
	
	public static function pwgen($length) {
		return substr(md5(rand().rand()), 0, $length);
	}
}
