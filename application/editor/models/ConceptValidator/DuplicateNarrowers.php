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
 * Validates that between two concepts only one relation Narrower is defined.
 *
 */
class Editor_Models_ConceptValidator_DuplicateNarrowers extends Editor_Models_ConceptValidator
{
	/**
	 * @see Editor_Models_ConceptValidator::validate($concept)
	 */
	public function isValid(Editor_Models_Concept $concept, $extraData)
	{
		$this->_setField('narrower');
		
		$isValid = true;
		if (isset($concept['narrower'])) {
			$model = Api_Models_Concepts::factory();
			foreach ($concept['narrower'] as $i => $narrower) {
				if (count(array_keys($concept['narrower'], $narrower)) > 1) {
					$isValid = false;
					$this->_addConflictedConcept(new Editor_Models_Concept($model->getConceptByUri($narrower, true)));
				}
			}
		}
		
		if ( ! $isValid) {
			$this->_setErrorMessage(_('Some concepts are defined more than once as narrower'));
		}
		
		return $isValid;
	}
	
	public static function factory()
	{
		return new Editor_Models_ConceptValidator_DuplicateNarrowers();
	}	
}