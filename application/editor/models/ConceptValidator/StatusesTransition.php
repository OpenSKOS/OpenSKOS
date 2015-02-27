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
 * Validates that the concept is at least in one scheme. 
 * 
 */
class Editor_Models_ConceptValidator_StatusesTransition extends Editor_Models_ConceptValidator
{
	/**
	 * @see Editor_Models_ConceptValidator::validate($concept)
	 */
	public function isValid(Editor_Models_Concept $concept, $extraData)
	{
		$this->_setField('status');
		
		$isValid = true;
        
        
        $response  = Api_Models_Concepts::factory()->getConcepts('uuid:'.$uuid);
		if (!isset($response['response']['docs']) || (1 !== count($response['response']['docs']))) {			
			throw new Zend_Exception('The requested concept was not found');
		} else {
			return new Editor_Models_Concept(new Api_Models_Concept(array_shift($response['response']['docs'])));
		}
		
        $oldConcept = null;
        if (null !== $oldConcept) {
            $isValid = OpenSKOS_Concept_Status::isTransitionAllowed($oldConcept['status'], $concept['status']);
        }        
        
		if ( ! $isValid) {
			$this->_setErrorMessage(
                sprintf(
                    _('The status transition from "%s" to "%s" is not allowed.'),
                    $oldConcept['status'],
                    $concept['status']
                )
            );
		}
		
		return $isValid;
	}
		
	public static function factory()
	{
		return new Editor_Models_ConceptValidator_StatusesTransition();
	}
}

