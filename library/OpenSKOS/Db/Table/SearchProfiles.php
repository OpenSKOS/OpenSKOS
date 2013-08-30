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
 * @copyright  Copyright (c) 2012 Pictura Database Publishing. (http://www.pictura-dp.nl)
 * @author     Alexandar Mitsev
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 */


class OpenSKOS_Db_Table_SearchProfiles extends Zend_Db_Table
{
	protected $_name = 'search_profiles';
	protected $_primary = 'id';

	/**
	 * Classname for row
	 *
	 * @var string
	 */
	protected $_rowClass = 'OpenSKOS_Db_Table_Row_SearchProfile';
	
	protected $_referenceMap = array (
			'User' => array (
					'columns' => 'creatorUserId',
					'refTableClass' => 'OpenSKOS_Db_Table_Users',
					'refColumns' => 'id'
			)
	);
	
	/**
	 * Adds new profile to search profiles.
	 * The options will be saved serialized.
	 * 
	 * @param string $name
	 * @param array $searchOptions
	 * @param int $creatorUserId
	 * @return int The new profile id
	 */
	public function addNew($name, $searchOptions, $creatorUserId, $tenant)
	{
		$data = array();
		$data['name'] = $name;
		$data['searchOptions'] = serialize($searchOptions);
		$data['creatorUserId'] = $creatorUserId;
		$data['tenant'] = $tenant;
		return $this->insert($data);
	}
}