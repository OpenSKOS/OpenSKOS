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
 * Validates that between two concepts only one relation Broader is defined.
 *
 */
class Editor_Models_ConceptValidator_DuplicateBroaders extends Editor_Models_ConceptValidator
{
	/**
	 * @see Editor_Models_ConceptValidator::validate($concept)
	 */
	public function isValid(Editor_Models_Concept $concept, $extraData)
	{
		$this->_setField('broader');
		
		$isValid = true;
		if (isset($concept['broader'])) {
			$model = Api_Models_Concepts::factory();
			foreach ($concept['broader'] as $i => $broader) {
				if (count(array_keys($concept['broader'], $broader)) > 1) {
					$isValid = false;
					$this->_addConflictedConcept(new Editor_Models_Concept($model->getConceptByUri($broader, true)));
				}
			}
		}
		
		if ( ! $isValid) {
			$this->_setErrorMessage(_('Some concepts are defined more than once as broader'));
		}
		
		return $isValid;
	}
	
	public static function factory()
	{
		return new Editor_Models_ConceptValidator_DuplicateBroaders();
	}
}