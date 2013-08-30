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
 * Validates that there is no cycle in the narrower relations.
 * 
 * This is not valid:
 * a NT b
 * b NT c
 * c NT a
 */
class Editor_Models_ConceptValidator_CycleInNarrowers extends Editor_Models_ConceptValidator
{
	/**
	 * @see Editor_Models_ConceptValidator::validate($concept)
	 */
	public function isValid(Editor_Models_Concept $concept, $extraData)
	{
		$this->_setField('narrower');
		
		$isValid = true;		
		$allNarrowers = $concept->getRelationsByField('narrower', null, array($concept, 'getAllRelations'));		
		foreach ($allNarrowers as $narrower) {
			$narrower = new Editor_Models_Concept($narrower);
			if ($narrower->hasRelationInDepth('narrower', $concept)) {
				$isValid = false;
				$this->_addConflictedConcept($narrower);
			}
		}
		
		if ( ! $isValid) {
			$this->_setErrorMessage(_('One or more of the narrower relations create a cycle'));
		}
		
		return $isValid;
	}
	
	public static function factory()
	{
		return new Editor_Models_ConceptValidator_CycleInNarrowers();
	}
}