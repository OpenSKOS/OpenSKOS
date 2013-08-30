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
 * Provides basic abstract class for concept validators
 * 
 */
abstract class Editor_Models_ConceptValidator
{
	/**
	 * Holds the field for which the validator is.
	 *
	 * @var string
	 */
	protected $_field = '';
	
	/**
	 * Holds the message of the error if not valid.
	 *
	 * @var string
	 */
	protected $_errorMessage = '';
	
	/**
	 * Holds an array of concepts which has conflicts wtht the current concept.
	 *
	 * @var array Array of Editor_Models_Concept objects
	 */
	protected $_errorConflictedConcepts = array();
	
	/**
	 * Validates the validated concept
	 * 
	 * @param Editor_Models_Concept $concept
	 * @param array Any extra data which will be used on saving.
	 * @return bool True if the concept is valid. False otherwise
	 */
	public abstract function isValid(Editor_Models_Concept $concept, $extraData);
	
	/**
	 * Gets the error of the validator.
	 * 
	 * @return Editor_Models_ConceptValidator_Error
	 */
	public function getError()
	{
		return new Editor_Models_ConceptValidator_Error($this->_field, $this->_errorMessage, $this->_errorConflictedConcepts);
	}
	
	/**
	 * Sets the field for which the validator is.
	 *
	 * @param string $field
	 */
	protected function _setField($field)
	{
		$this->_field = $field;
	}
	
	/**
	 * Sets the error message for the error of the validator.
	 *
	 * @param string $errorMessage
	 */
	protected function _setErrorMessage($errorMessage)
	{
		$this->_errorMessage = $errorMessage;
	}
	
	/**
	 * Adds a concept to the conflicted concepts which will be part of the error of the validator.
	 *
	 * @param Editor_Models_Concept $conflictedConcept
	 */
	protected function _addConflictedConcept($conflictedConcept)
	{
		$this->_errorConflictedConcepts[] = $conflictedConcept;
	}
	
	public static function factory()
	{
		return new Editor_Models_ConceptValidator();
	}
}