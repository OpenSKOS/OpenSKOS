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


class OpenSKOS_Db_Table_Row_SearchProfile extends Zend_Db_Table_Row
{
	/**
	 * Sets search options
	 *
	 * @param array $options
	 */
	public function setSearchOptions($options)
	{
		$this->searchOptions = serialize($options);
	}
	
	/**
	 * Gets search options
	 *
	 * @return array
	 */
	public function getSearchOptions()
	{
		$savedOptions = unserialize($this->searchOptions);
		
		// Merge with default options to be sure that we have correct value for any new options which are not saved in the profile.
		$defaultOptions = array();
		if (class_exists('Editor_Forms_SearchOptions')) {
			$defaultOptions = Editor_Forms_SearchOptions::getDefaultSearchOptions();
		}
		
		return array_merge($defaultOptions, $savedOptions);
	}
}