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

class Editor_Forms_Concept extends OpenSKOS_Form
{	
	/**
	 * A flag indicating that the form is for create.
	 * 
	 * @var bool
	 */
	protected $_isCreate = false;
	
	/**
	 * A flag indicating that the form is for proposal only.
	 * 
	 * @var bool
	 */
	protected $_isProposalOnly = false;
	
	public function init() 
	{
		$this->setName("Edit concept");
		$this->setMethod('Post');
		
		$this->_isProposalOnly = ( ! (OpenSKOS_Db_Table_Users::fromIdentity()->isAllowed('editor.concepts', 'full-create') || OpenSKOS_Db_Table_Users::fromIdentity()->isAllowed('editor.concepts', 'edit')));
		
		$this->buildHeader()
		->buildTabsControl()
		->buildLanguageTabs()
		->buildSchemeTabs();
	}
	
	/**
	 * Sets the flag isCreate. If true - the form is in create mode.
	 * 
	 * @param bool $isCreate
	 */
	public function setIsCreate($isCreate)
	{
		$this->_isCreate = $isCreate;
	}
	
	/**
	 * Gets the flag isCreate.
	 *
	 * @return bool $isCreate
	 */
	public function getIsCreate()
	{
		return $this->_isCreate;
	}
	
	/**
	 * This builds the form header.
	 * Holds the status, to be checked and action buttons for the concept form.
	 * 
	 * @return Editor_Forms_Concept
	 */
	protected function buildHeader()
	{
		$this->addElement('hidden', 'uuid', array(
			'decorators' => array()
		));

		$availableStatuses = array();
		$availableStatuses[] = 'candidate';		
		
		if ( ! $this->_isProposalOnly) {			
			$availableStatuses[] = 'approved';
			$availableStatuses[] = 'expired';
			
			$this->addElement('checkbox', 'toBeChecked', array(
					'label' => 'To be checked:',
					'decorators' => array('ViewHelper', 'Label', array('HtmlTag', array('tag' => 'span', 'id' => 'concept-edit-checked')))
			));
		}
		
		$this->addElement('radio', 'status', array(
				'label' => 'Status:',
				'separator' => '',
				'multiOptions' => array_combine($availableStatuses, $availableStatuses),
				'value' => 'candidate',
				'decorators' => array('ViewHelper', 'Label', array('HtmlTag', array('tag' => 'span', 'id' => 'concept-edit-status')))
		));
		
		if ($this->_isProposalOnly) {
			$this->getElement('status')->setValue('candidate');
		}
		
		if ( ! $this->_isCreate) {
			
			$this->addElement('submit', 'conceptSave', array(
					'label' => _('Ok'),
					'class' => 'concept-edit-submit',
					'decorators' => array('ViewHelper', array('HtmlTag', array('tag' => 'span', 'id' => 'concept-edit-action', 'openOnly' => true)))
			));
			
			$this->addElement('submit', 'conceptSwitch', array(
					'label' => _('Switch to view mode'),
					'class' => 'concept-edit-view',
					'decorators' => array('ViewHelper')
			));
			
			$this->addElement('button', 'conceptExport', array(
					'label' => _('Export'),
					'class' => 'export-concept',
					'decorators' => array('ViewHelper')
			));
			
			$this->addElement('button', 'conceptDelete', array(
					'label' => _('Delete'),
					'class' => 'delete-concept',
					'decorators' => array('ViewHelper', array('HtmlTag', array('tag' => 'span','closeOnly' => true)))
			));
		} else {
			$this->addElement('submit', 'conceptSave', array(
					'label' => _('Ok'),
					'class' => 'concept-edit-submit',
					'decorators' => array('ViewHelper', array('HtmlTag', array('tag' => 'span', 'id' => 'concept-edit-action')))
			));
		}
		
		$this->addDisplayGroup(
				array('status', 'toBeChecked', 'conceptSave', 'conceptSwitch', 'conceptExport', 'conceptDelete'), 
				'concept-header', 
				array(
					'legend' => 'header',
					'disableDefaultDecorators'=> true,
					'decorators'=> array('FormElements', array('HtmlTag', array('tag' => 'div', 'id' => 'concept-edit-header')))));
		return $this;
	}
	
	/**
	 * This builds the tabs control and the modals content for adding a language layer or a concept scheme layer.
	 * 
	 * @return Editor_Forms_Concept
	 */	
	protected function buildTabsControl()
	{
		$languageTabs = new OpenSKOS_Form_Element_Multihidden('conceptLanguages');
		$languageTabs->setCssClasses(array('concept-form-left'));
		$this->addElement($languageTabs);

		$schemeTabs = new OpenSKOS_Form_Element_Multihidden('inScheme');
		$schemeTabs->setCssClasses(array('concept-form-right'));
		$this->addElement($schemeTabs);
		
		$editorOptions = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getOption('editor');
		$languages = $editorOptions['languages'];
		
		$this->addElement('select', 'conceptLanguageSelect', array(
				'label' => 'Select a language',
				'multiOptions' => $languages,
				'decorators' => array('ViewHelper', 'Label'),
				'validators' => array()
				));
		
		$this->addElement('submit', 'conceptLanguageOk', array(
				'label' => 'Add',
				'decorators' => array('ViewHelper')));
		
		$this->addElement('select', 'conceptSchemeSelect', array(
				'label' => 'Select a concept scheme',
				'decorators' => array('ViewHelper'),
				'registerInArrayValidator' => false
			));
		
		$this->addElement('submit', 'conceptSchemeOk', array(
				'label' => 'Add',
				'decorators' => array('ViewHelper')));
		
		
		$this->addDisplayGroup(
				array('conceptLanguageSelect', 'conceptLanguageOk'),
				'concept-language-overlay',
				array(
						'legend' => 'header',
						'disableDefaultDecorators'=> true,
						'decorators'=> array('FormElements', array('HtmlTag', array('tag' => 'div', 'id' => 'concept-language-settings', 'class' => 'do-not-show')))));

		$this->addDisplayGroup(
				array('conceptSchemeSelect', 'conceptSchemeOk'),
				'concept-scheme-overlay',
				array(
						'legend' => 'header',
						'disableDefaultDecorators'=> true,
						'decorators'=> array('FormElements', array('HtmlTag', array('tag' => 'div', 'id' => 'concept-scheme-settings', 'class' => 'do-not-show')))));
		
		$this->addDisplayGroup(
				array('conceptLanguages', 'inScheme'),
				'concept-tabs',
				array(
						'legend' => 'header',
						'disableDefaultDecorators'=> true,
						'decorators'=> array('FormElements', array('HtmlTag', array('tag' => 'div', 'id' => 'concept-edit-tabs')))));
		return $this;
	}
	
	/**
	 * This builds the content that will be hidden/shown depending on language.
	 * Tabbing in the form is different than tabbing elsewhere because of the Zend_Form grouping limitations.
	 * 
	 * @return Editor_Forms_Concept
	 */
	protected function buildLanguageTabs()
	{
		$this->addElement('hidden', 'wrapLeftTop', array(
				'decorators' => array('ViewHelper', array('HtmlTag', array('tag' => 'div', 'id' => 'concept-edit-left', 'openOnly'  => true)))
				));
		
		
		$labels = array();
		$labels['prefLabel'] = 'Preferred label';
		
		$documentProperties = array();
		$documentProperties['scopeNote'] = 'Scope note';
		$documentProperties['note'] = 'Note';
		$documentProperties['example'] = 'Example';
		
		if ( ! $this->_isProposalOnly) {
			
			$labels['altLabel'] = 'Alternative label';
			$labels['hiddenLabel'] = 'Hidden label';
			
			$documentProperties['definition'] = 'Definition';
			$documentProperties['changeNote'] = 'Change note';			
			$documentProperties['editorialNote'] = 'Editorial note';
			$documentProperties['historyNote'] = 'History note';
		}
		
		$this->buildMultiElements($labels, 'OpenSKOS_Form_Element_Multitext' ,array(), null, 'concept-edit-language-labels');
		$this->buildMultiElements($documentProperties, 'OpenSKOS_Form_Element_Multitextarea', array(), null,'concept-edit-language-properties');
		
		$this->addElement('select', 'conceptPropertySelect', array(
				'label' => '', 
				'multiOptions' => array_merge(array('' => ''), $documentProperties),
				'decorators' => array('ViewHelper', array('HtmlTag', array('tag' => 'div', 'id' => 'concept-edit-property-selector')))));
		
		$this->addElement('textarea', 'conceptPropertyContent', array(
				'label' => '', 
				'rows' => 2,
				'decorators' => array('ViewHelper', array('HtmlTag', array('tag' => 'div', 'id' => 'concept-edit-property-content')))));
		
		$this->addElement('submit', 'conceptPropertyAdd', array(
				'label' => _('Add documentation property'),
				'class' => 'concept-edit-property-action',
				'decorators' => array('ViewHelper', array('HtmlTag', array('tag' => 'div', 'id' => 'concept-edit-property-action')))
		));
		
		$this->addElement('hidden', 'wrapLeftBottom', array(
				'decorators' => array('ViewHelper', array('HtmlTag', array('tag' => 'div', 'closeOnly'  => true)))
				));

		return $this;
	}
	
	/**
	 * @return Editor_Forms_Concept
	 */	
	protected function buildSchemeTabs()
	{
		$this->addElement('hidden', 'wrapRightTop', array(
				'decorators' => array('ViewHelper', array('HtmlTag', array('tag' => 'div', 'id' => 'concept-edit-right', 'openOnly'  => true)))
		));
		
		if ( ! $this->_isProposalOnly) {
			$this->buildMultiElements(array(
					'broader' => _('Has broader'),
					'narrower' => _('Has narrower'),
					'related' => _('Has related')
			),'OpenSKOS_Form_Element_Multilink' ,array(), null, 'concept-edit-scheme-relations');
		}
		
		$this->buildMappingProperties();
		
		if ($this->_isCreate) {
			$this->addElement('select', 'baseUri', array(
					'label' => 'Base URI:',
					'decorators' => array('ViewHelper', 'Label')
			));
			$this->getElement('baseUri')->setRegisterInArrayValidator(false);
			
			$this->addElement('hidden', 'hiddenBr1', array(
					'decorators' => array('ViewHelper', array('HtmlTag', array('tag' => 'br', 'openOnly'  => true)))
			));
		}
		
		$this->addElement('text', 'uri', array(
				'label' => 'URI:',
				'readonly' => true,
				'decorators' => array('ViewHelper', 'Label')
		));
		
		$this->addElement('hidden', 'hiddenBr2', array(
				'decorators' => array('ViewHelper', array('HtmlTag', array('tag' => 'br', 'openOnly'  => true)))
		));
		
		$this->addElement('text', 'notation', array(
				'label' => 'Notation:',
				'readonly' => true,
				'decorators' => array('ViewHelper', 'Label')
		));
		
		
		$this->addElement('hidden', 'hiddenBr3', array(
				'decorators' => array('ViewHelper', array('HtmlTag', array('tag' => 'br', 'openOnly'  => true)))
		));
		
		$this->addElement('multiCheckbox', 'topConceptOf', array(
				'label' => 'Is top concept:',
				'decorators' => array('ViewHelper', 'Label'),
				'registerInArrayValidator' => false
		));
		
		$this->addDisplayGroup(
				array('conceptUri', 'conceptNotation', 'topConceptOf'),
				'concept-edit-scheme',
				array(
						'legend' => 'header',
						'disableDefaultDecorators'=> true,
						'decorators'=> array('FormElements', array('HtmlTag', array('tag' => 'div', 'id' => 'concept-edit-scheme-properties')))));
		
		
		$this->addElement('hidden', 'wrapRightBottom', array(
				'decorators' => array('ViewHelper', array('HtmlTag', array('tag' => 'div', 'closeOnly'  => true)))
		));
		
		return $this;
	}
	
	/**
	 * Straight forward - mapping properties section.
	 * @return Editor_Forms_Concept
	 */
	protected function buildMappingProperties()
	{
		if ( ! $this->_isProposalOnly) {	
					
			$mappingNames = array(
					'broadMatch' => _('Has broader match'),
					'narrowMatch' => _('Has narrower match'),
					'relatedMatch' => _('Has related match'),
					'mappingRelation' => _('Has mapping relation'),
					'closeMatch' => _('Has close match'),
					'exactMatch' => _('Has exact match')
			);
			
			$this->buildMultiElements($mappingNames, 'OpenSKOS_Form_Element_Multilink', array(), null, 'concept-edit-mapping-properties');
		}
		
		return $this;
	}
	
	public static function getHelperFields ()
	{
		return array(
				'conceptLanguages',
				'conceptSchemes',
				'conceptLanguageSelect',
				'conceptSchemeSelect',
				'wrapLeftTop',
				'conceptPropertySelect',
				'conceptPropertyContent',
				'wrapLeftBottom',
				'wrapRightTop',
				'wrapRightBottom');
	}

	/**
	 * @return Editor_Forms_Concept
	 */
	public static function getInstance($isCreate = false)
	{
		static $instance;
	
		if (null === $instance) {
			$instance = new Editor_Forms_Concept(array('isCreate' => $isCreate));
		}
	
		return $instance;
	}
}