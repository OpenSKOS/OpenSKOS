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
 * Validates that the concept is not with status expired while it has relations or other concepts has relations to it.
 * Also validates if any of the related concept does not have status expired.
 * 
 */
class Editor_Models_ConceptValidator_ExpiredWithRelations extends Editor_Models_ConceptValidator
{
	/**
	 * @see Editor_Models_ConceptValidator::validate($concept)
	 */
	public function isValid(Editor_Models_Concept $concept, $extraData)
	{
		$this->_setField('status');
		
		$isValid = true;
		
		if ($extraData['status'] == 'expired' && $concept->hasAnyRelations()) {
			$isValid = false;
			$this->_setErrorMessage(_('Can not change status to "expired" while there are still relations to other concepts.'));
		} else {
			
			// Check if there are any concepts in the relations which are expired.
			$allRelations = $concept->getAllRelationsAndMappings();
			foreach ($allRelations as $relations) {
				if (! empty($relations)) {
					foreach ($relations as $relatedConcept) {
						if ($relatedConcept['status'] == 'expired') {
							$isValid = false;
							$relatedConcept = new Editor_Models_Concept($relatedConcept);
							$this->_addConflictedConcept($relatedConcept);
						}
					}
				}
			}
			
			if ( ! $isValid) {
				$this->_setErrorMessage(_('Can not relate the concept to any expired concepts. The fallowing concepts are expired:'));
			}
		}
		
		return $isValid;
	}
		
	public static function factory()
	{
		return new Editor_Models_ConceptValidator_ExpiredWithRelations();
	}
}

