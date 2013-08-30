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


class OpenSKOS_Db_Table_Notations extends Zend_Db_Table
{
	protected $_name = 'notations';
	protected $_primary = 'notation';

	/**
	 * Classname for row
	 *
	 * @var string
	 */
	protected $_rowClass = 'OpenSKOS_Db_Table_Row_Notation';
	
	/**
	 * Gets next notation and registers it in the database.
	 * 
	 * @return string
	 */
	public static function getNext()
	{
		$model = new self();
		return $model->insert(array());
	}
	
	/**
	 * Check is the given notation registered in the database.
	 * 
	 * @param string $notation
	 * @return bool
	 */
	public static function isRegistered($notation)
	{
		$model = new self();
		$check = $model->find($notation);
		return $check->count() != 0;
	}
	
	/**
	 * Inserts the notation in the database.
	 *
	 * @param string $notation
	 */
	public static function register($notation)
	{
		$model = new self();
		return $model->insert(array('notation' => $notation));
	}
}