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
 * @copyright  Copyright (c) 2014 Pictura Database Publishing. (http://www.pictura-dp.nl)
 * @author     Alexandar Mitsev
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 */

/**
 * Validates that the concept has unique notation. 
 * 
 */
class Editor_Models_ConceptValidator_UniqueNotation extends Editor_Models_ConceptValidator
{
	/**
	 * @see Editor_Models_ConceptValidator::validate($concept)
	 */
	public function isValid(Editor_Models_Concept $concept, $extraData)
	{
		$this->_setField('notation');		
		$isValid = true;
		
        if (!isset($concept['notation']) || empty($concept['notation'])) {
            $this->_setErrorMessage(_('Notation not specified.'));
            $isValid = false;
        } else {
            
            $query = 'notation:"' . $concept['notation'] . '"';
            $query .= ' tenant:"' . OpenSKOS_Db_Table_Tenants::fromIdentity()->code . '"';
			$query .= ' -uuid:"' . $concept['uuid'] . '"';

			$response = Api_Models_Concepts::factory()->setQueryParams(array('rows' => 0))->getConcepts($query);
			if ($response['response']['numFound'] > 0) {
                $this->_setErrorMessage(_('System error. The notation of this concept is already used.'));
				$isValid = false;
			}
        }
        
		return $isValid;
	}
		
	public static function factory()
	{
		return new Editor_Models_ConceptValidator_UniqueNotation();
	}
}

