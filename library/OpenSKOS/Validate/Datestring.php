<?php

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
