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
 * Validates that there is no direct narrower relation between concepts which has also the same relation via intermidate concept(s).
 * 
 * E.g. The following is ok.
 * - Birds NT Song Birds
 * - Song Birds NT Canaries
 * 
 * The following is not ok.
 * - Birds NT Song Birds
 * - Birds NT Canaries
 * - Song Birds NT Canaries
 *
 */
class Editor_Models_ConceptValidator_UnneededDirectNarrowers extends Editor_Models_ConceptValidator
{
	/**
	 * @see Editor_Models_ConceptValidator::validate($concept)
	 */
	public function isValid(Editor_Models_Concept $concept, $extraData)
	{
		$this->_setField('broader');
		
		$isValid = true;
		$allNarrowers = $concept->getRelationsByField('narrower', null, array($concept, 'getAllRelations'));		
		foreach ($allNarrowers as $narrower) {
			$narrower = new Editor_Models_Concept($narrower);
			if ($concept->hasRelationInDepth('narrower', $narrower, false)) {
				$isValid = false;
				$this->_addConflictedConcept($narrower);
			}
		}
		
		if ( ! $isValid) {
			$this->_setErrorMessage(_('One or more of narrower relations have also a relation via an intermediate concepts'));
		}
		
		return $isValid;
	}
	
	public static function factory()
	{
		return new Editor_Models_ConceptValidator_UnneededDirectNarrowers();
	}
}