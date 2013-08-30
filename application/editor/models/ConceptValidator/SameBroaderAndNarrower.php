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
 * Validates that there is no dierect cycle in the broader and narrower relations.
 * 
 * This is not valid:
 * a BT b
 * a NT b
 */
class Editor_Models_ConceptValidator_SameBroaderAndNarrower extends Editor_Models_ConceptValidator
{
	/**
	 * @see Editor_Models_ConceptValidator::validate($concept)
	 */
	public function isValid(Editor_Models_Concept $concept, $extraData)
	{
		$this->_setField('broader');
		
		$isValid = true;		
		$allBroaders = $concept->getRelationsByField('broader', null, array($concept, 'getAllRelations'));
		$allNarrowers = $concept->getRelationsByField('narrower', null, array($concept, 'getAllRelations'));
		
		$matches = array_uintersect($allBroaders, $allNarrowers, array('Api_Models_Concept', 'compare'));
		
		if ( ! empty($matches)) {
			$isValid = false;
			foreach ($matches as $broader) {
				$broader = new Editor_Models_Concept($broader);
				$this->_addConflictedConcept($broader);
			}
		}
		
		if ( ! $isValid) {
			$this->_setErrorMessage(_('One or more of the broader relations create a cycle'));
		}
		
		return $isValid;
	}
		
	public static function factory()
	{
		return new Editor_Models_ConceptValidator_SameBroaderAndNarrower();
	}
}

