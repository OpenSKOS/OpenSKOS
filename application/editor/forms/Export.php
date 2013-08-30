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

class Editor_Forms_Export extends Zend_Form
{
	public function init() 
	{
		$this->setName('exportform')
		->setAction(Zend_Controller_Front::getInstance()->getRouter()->assemble(array('controller'=>'concept', 'action' => 'export')))
		->setMethod('post');

		$this->buildFileName()
		->buildFormat()
		->buildDepthSelector()
		->buildFieldsSelector()		
		->buildHiddenInputs()
		->buildButtons();
	}
	
	protected function buildFileName()
	{
		$this->addElement('text', 'fileName', array(
            'filters' => array('StringTrim'),
            'label' => 'File name',
			'decorators' => array('ViewHelper', 'Label', array('HtmlTag', array('tag' => 'br', 'placement' => Zend_Form_Decorator_HtmlTag::APPEND))),
        ));
		return $this;
	}
	
	protected function buildFormat()
	{
		$this->addElement('select', 'format', array(
				'label' => 'Format',
				'multiOptions' => Editor_Models_Export::getExportFormats(),
				'decorators' => array('ViewHelper', 'Label', array('HtmlTag', array('tag' => 'br', 'placement' => Zend_Form_Decorator_HtmlTag::APPEND))),
		));
		return $this;
	}
	
	protected function buildFieldsSelector()
	{
		$exportableFields = Editor_Models_Export::getExportableConceptFields();
		$exportableFields = array_combine($exportableFields, $exportableFields);
		$exportableFields = array_merge(array('' => _('Select one')), $exportableFields);
		$this->addElement('select', 'exportableFields', array(
				'label' => 'Fields to export',
				'class' => 'exportable-fields',
				'multiOptions' => $exportableFields,
				'decorators' => array(),
		));
		
		$this->addElement('button', 'addToExportFields', array(
				'label' => 'Add',
				'class' => 'add-to-export',
				'decorators' => array(),
		));
		
		$this->addElement('hidden', 'fieldsToExport', array(
				'filters' => array('StringTrim'),
				'label' => '',
				'decorators' => array('ViewHelper', array('HtmlTag', array('tag' => 'ul', 'class' => 'export-fields', 'placement' => Zend_Form_Decorator_HtmlTag::APPEND)))
		));
		
		$this->addDisplayGroup(
				array('exportableFields', 'addToExportFields', 'fieldsToExport'),
				'fields-selector',
				array('disableDefaultDecorators' => true, 'decorators'=> array('FormElements', array('HtmlTag', array('tag' => 'div', 'class' => 'fields-selector')))));
		
		return $this;
	}
	
	protected function buildDepthSelector()
	{
		$depths = array();
		for ($i = 1; $i < 21; $i ++) {
			$depths[$i] = $i;
		}
		
		$this->addElement('select', 'maxDepth', array(
				'label' => 'Depth',
				'multiOptions' => $depths,
				'decorators' => array('ViewHelper', 'Label', array('HtmlTag', array('tag' => 'br', 'placement' => Zend_Form_Decorator_HtmlTag::APPEND))),
		));
		
		$this->addDisplayGroup(
				array('maxDepth'),
				'depth-selector',
				array('disableDefaultDecorators' => true, 'decorators'=> array('FormElements', array('HtmlTag', array('tag' => 'div', 'class' => 'depth-selector')))));
		
		
		return $this;
	}
	
	protected function buildHiddenInputs()
	{
		$this->addElement('hidden', 'type', array(
				'filters' => array('StringTrim'),
				'label' => '',
				'decorators' => array(),
		));
		$this->addElement('hidden', 'additionalData', array(
				'filters' => array('StringTrim'),
				'label' => '',
				'decorators' => array(),
		));
		$this->addElement('hidden', 'currentUrl', array(
				'filters' => array('StringTrim'),
				'label' => '',
				'decorators' => array(),
		));
		return $this;
	}
	
	protected function buildButtons()
	{
		$this->addElement('submit', 'exportButton', array(
				'label' => 'Export',
				'decorators' => array(),
		));
		return $this;
	}
	
	/**
	 * @return Editor_Forms_Export
	 */
	public static function getInstance()
	{
		static $instance;
		
		if (null === $instance) {
			$instance = new Editor_Forms_Export();
		}
		
		return $instance;
	}
}