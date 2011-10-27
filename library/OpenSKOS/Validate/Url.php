<?php

class OpenSKOS_Validate_Url extends Zend_Validate_Abstract 
{
	const INVALID_URL = 'invalidUrl';
	protected $_messageTemplates = array (self::INVALID_URL => "'%value%' is not a valid URL." );
	
	public function isValid($value) {
		$valueString = ( string ) $value;
		$this->_setValue ( $valueString );
		
		if (! Zend_Uri::check ( $value )) {
			$this->_error ( self::INVALID_URL );
			return false;
		}
		return true;
	}
}
