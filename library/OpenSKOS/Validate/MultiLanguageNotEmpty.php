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

/**
 * Validates a multi language field to be not empty in each language.
 * 
 */
class OpenSKOS_Validate_MultiLanguageNotEmpty extends Zend_Validate_Abstract 
{
	const IS_EMPTY = 'isEmpty';
	protected $_messageTemplates = array(self::IS_EMPTY => "This field is required and must be filled in each language.");
	
	public function isValid($value) 
	{
		foreach ($value as $languageCode => $perLangValue) {
			if ( ! empty($languageCode)) {
				if (is_array($perLangValue)) {
					array_walk($perLangValue, 'trim');
					$valueToCheck = implode('', $perLangValue);
				} else {
					$valueToCheck = trim($perLangValue);
				}
				 
				if (empty($valueToCheck)) {
					$this->_error(self::IS_EMPTY);
					return false;
				}
			}
		}
		
		return true;
	}
}
