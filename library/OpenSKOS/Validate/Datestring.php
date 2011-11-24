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

class OpenSKOS_Validate_Datestring extends Zend_Validate_Abstract 
{
	const INVALID_DATESTRING = 'invalidDatestring';
	protected $_messageTemplates = array (self::INVALID_DATESTRING => "'%value%' is not a valid date string." );
	
	public function isValid($value) {
		$valueString = ( string ) $value;
		$this->_setValue ( $valueString );
		
		if (false === strtotime($valueString)) {
			$this->_error ( self::INVALID_DATESTRING );
			return false;
		}
		return true;
	}
}
