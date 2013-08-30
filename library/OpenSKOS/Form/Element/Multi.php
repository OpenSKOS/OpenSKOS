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
 * @author     Boyan Bonev
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 */

/** 
 * This is a parent element class that allows us to have multi values with dynamic field additions with input types other than the defined in the Zend framework.
 * 
 * @TODO Should be made abstract by switching the render function to a wrapper around an internal render method.
 */
class OpenSKOS_Form_Element_Multi extends Zend_Form_Element_Multi
{
	/**
	 * This is the name of the element. The Zend_Form indexes it by this name and later populates by it.
	 * @var string
	 */
	public $groupName; 
	
	/**
	 *  We store the values for the render call in here.
	 * @var array
	 */
	public $groupValues;
	
	/**
	 * We store the label for the group.
	 * @var string
	 */
	public $groupLabel;
	
	/**
	 * var array
	 */
	public $cssClasses;
	
	/**
	 * View path associated with the element
	 */	
	protected $_partialView;
	
	public function __construct($groupName, $groupLabel)
	{
		$this->groupName = $groupName;
		$this->setGroupLabel($groupLabel);
		$this->setCssClasses();
		parent::__construct($groupName);
	}

	/**
 	 * @return OpenSKOS_Form_Element_Multi
	 */
	public function setGroupLabel($groupLabel = null)
	{
		$this->groupLabel = $groupLabel;
		return $this;
	}
	
	/**
	 * @return OpenSKOS_Form_Element_Multi
	 */
	public function setGroupName($groupName = null)
	{
		$this->groupName = $groupName;
		return $this;
	}

	/**
	 * @return OpenSKOS_Form_Element_Multi
	 */
	public function setPartialView($partialView)
	{
		$this->_partialView = $partialView;
		return $this;
	}
	
	/**
	 * @return OpenSKOS_Form_Element_Multi
	 */
	public function setCssClasses(array $classes = array())
	{
		$this->cssClasses = $classes;
		return $this;
	}	

	/**
	 * Zend_Form calls this function on populate, it allows us to initialize the form content into the controller.
	 * @param array $values
	 * @return OpenSKOS_Form_Element_Multi
	 */
	public function setValue($values)
	{		
		$this->groupValues = $values ;
		return $this;
	}
	
	/**
	 * Needed for form population.
	 */
	public function getValue()
	{
		return $this->groupValues;
	}
	
	/**
	 * Is the value provided valid?
	 * Sets flag RegisterInArrayValidator to false and executes the parent isValid
	 *
	 * @param  string $value
	 * @param  mixed $context
	 * @return bool
	 */
	public function isValid($value, $context = null)
	{
		$this->setRegisterInArrayValidator(false);
		return parent::isValid($value, $context);
	}
		
	/**
	 * Zend_Form calls this function on display. we render it with a partial view to support dynamic addition of multiple elements.
	 * Validation will need additional work.
	 * @param Zend_View_Interface $view
	 */
	public function render (Zend_View_Interface $view = null)
	{
		if (null !== $view) {
			$this->setView($view);
		}
		return $this->_view->partial($this->_partialView, array(
				'inputName' => $this->groupName,
				'inputValues' => $this->groupValues,
				'inputLabel' => $this->groupLabel,
				'inputClasses' => implode(' ', $this->cssClasses)));
	}
}