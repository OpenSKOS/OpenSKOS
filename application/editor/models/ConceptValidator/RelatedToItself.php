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
 * Validates that the concept is not related to itself.
 * 
 */
class Editor_Models_ConceptValidator_RelatedToItself extends Editor_Models_ConceptValidator
{
	/**
	 * @see Editor_Models_ConceptValidator::validate($concept)
	 */
	public function isValid(Editor_Models_Concept $concept, $extraData)
	{
		$this->_setField('relations');
		
		$isValid = true;
		$relationFields = array_merge(Editor_Models_Concept::$classes['SemanticRelations'], Editor_Models_Concept::$classes['MappingProperties']);
		
		foreach ($relationFields as $field) {
			if (isset($concept[$field]) && ! empty($concept[$field]) 
					&& in_array($concept['uri'], $concept[$field])) {
				$isValid = false;
				break;
			}
		}
		
		if ( ! $isValid) {
			$this->_setErrorMessage(_('The concept can not be related to itself.'));
		}
		
		return $isValid;
	}
		
	public static function factory()
	{
		return new Editor_Models_ConceptValidator_RelatedToItself();
	}
}

