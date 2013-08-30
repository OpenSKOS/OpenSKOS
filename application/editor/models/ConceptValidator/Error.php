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

class Editor_Models_ConceptValidator_Error
{
	/**
	 * Holds the field for which is the validator.
	 *
	 * @var string
	 */
	private $_field;
	
	/**
	 * Holds the error message of the validator.
	 *
	 * @var string
	 */
	private $_message;
	
	/**
	 * Holds an array of concepts which has conflicts wtht the current concept.
	 *
	 * @var array Array of Editor_Models_Concept objects
	 */
	private $_conflictedConcepts = array();
	
	public function __construct($field, $message, $conflictedConcepts = array())
	{
		$this->_field = $field;
		$this->_message = $message;
		$this->_conflictedConcepts = $conflictedConcepts;
	}
	
	/**
	 * Gets the field for which the validator is.
	 *
	 * @return string
	 */
	public function getField()
	{
		return $this->_message;
	}
	
	/**
	 * Gets the error message of the validator.
	 *
	 * @return string
	 */
	public function getMessage()
	{
		return $this->_message;
	}
	
	/**
	 * Gets the concepts which has conflicts with the validated concept.
	 *
	 * @return array Array of Editor_Models_Concept objects
	 */
	public function getConflictedConcepts()
	{
		return $this->_conflictedConcepts;
	}
}