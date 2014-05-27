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

class Editor_Forms_ConceptScheme extends OpenSKOS_Form
{
	/**
	 * A flag indicating that the form is for create.
	 *
	 * @var bool
	 */
	protected $_isCreate = false;

	/**
	 * Holds the available languages for the form.
	 * 
	 * @var array
	 */
	protected $_languages = array();
	
	/**
	 * Holds the inital default language for the form.
	 *
	 * @var string
	 */
	protected $_defaultLanguage = '';
	
	/**
	 * Holds the currently logged user's tenant.
	 *
	 * @var OpenSKOS_Db_Table_Row_Tenant
	 */
	protected $_currentTenant;
	
	public function init()
	{
		$this->setName('Edit concept scheme');
		$this->setMethod('Post');
		
		$this->initLanguages();
		
		$this->buildHeader()
		->buildTabsControl()
		->buildLanguageTabs();
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
	 * Init languages and default language
	 * 
	 * @return Editor_Forms_ConceptScheme
	 */
	protected function initLanguages()
	{
		$editorOptions = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getOption('editor');
		$this->_languages = $editorOptions['languages'];
		
		$this->_defaultLanguage = Zend_Registry::get('Zend_Locale')->getLanguage();
		if ( ! isset($this->_languages[$this->_defaultLanguage])) {
			$availabaleLanguages = array_keys($this->_languages);
			$this->_defaultLanguage = $availabaleLanguages[0];
		}
		
		return $this;
	}

	/**
	 * This builds the buttons for the form.
	 *
	 * @return Editor_Forms_ConceptScheme
	 */
	protected function buildHeader()
	{
		$this->addElement('hidden', 'uuid', array('decorators' => array()));
		
		// Collections
		$modelCollections = new OpenSKOS_Db_Table_Collections();
		$collectionOptions = $modelCollections->getIdToTitleMap($this->_getCurrentTenant()->code);
		
		$this->addElement('select', 'collection', array(
				'label' => _('Collection:'),
				'multiOptions' => $collectionOptions,
				'decorators' => array('ViewHelper', 'Label', array('HtmlTag', array('tag' => 'br', 'placement' => Zend_Form_Decorator_HtmlTag::APPEND)))
		));
		
		// Uri code
		$this->addElement('text', 'uriBase', array(
				'label' => 'URI: ',
				'decorators' => array('ViewHelper', 'Label'),
				'filters' => array('StringTrim')
		));
		$this->getElement('uriBase')->setRequired(true);
		
		$this->addElement('text', 'uriCode', array(
				'decorators' => array('ViewHelper'),
				'filters' => array('StringTrim')
		));
		$this->getElement('uriCode')->setRequired(true);
		
		$this->addElement('submit', 'conceptSchemeSave', array(
				'label' => _('Ok'),
				'class' => 'concept-edit-submit',
				'decorators' => array('ViewHelper', array('HtmlTag', array('tag' => 'span', 'id' => 'concept-edit-action')))
		));
		
		$this->addDisplayGroup(
				array('collection', 'uriBase', 'uriCode', 'conceptSchemeSave'),
				'concept-header',
				array('legend' => 'header',
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
		$languageTabs->setValue(array(strtoupper($this->_defaultLanguage) => array(strtoupper($this->_defaultLanguage) => $this->_defaultLanguage)));
		$languageTabs->setCssClasses(array('concept-form-left'));
		$this->addElement($languageTabs);
	
		$this->addElement('select', 'conceptLanguageSelect', array(
				'label' => 'Select a language',
				'multiOptions' => $this->_languages,
				'decorators' => array('ViewHelper', 'Label'),
				'validators' => array()
		));
	
		$this->addElement('submit', 'conceptLanguageOk', array(
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
				array('conceptLanguages'),
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
			'decorators' => array('ViewHelper', array('HtmlTag', array('tag' => 'div', 'id' => 'concept-scheme-edit-dcterms', 'openOnly'  => true)))
		));
		
		$this->addElement(new OpenSKOS_Form_Element_Multitext('dcterms_title', 'Title'));
		$this->getElement('dcterms_title')->setValue(array(array('languageCode' => $this->_defaultLanguage, 'value' => array(''))));
		$this->getElement('dcterms_title')->addValidator(new OpenSKOS_Validate_MultiLanguageNotEmpty());
		
		$this->addElement(new OpenSKOS_Form_Element_Multitextarea('dcterms_description', 'Description'));
		$this->getElement('dcterms_description')->setValue(array(array('languageCode' => $this->_defaultLanguage, 'value' => array(''))));
		
		$this->addElement(new OpenSKOS_Form_Element_Multitextarea('dcterms_creator', 'Creator'));
		$this->getElement('dcterms_creator')->setValue(array(array('languageCode' => $this->_defaultLanguage, 'value' => array($this->_getCurrentTenant()->name))));
		
		$this->addElement('hidden', 'wrapLeftBottom', array(
			'decorators' => array('ViewHelper', array('HtmlTag', array('tag' => 'div', 'closeOnly'  => true)))
		));
		
		return $this;
	}
	
	/**
	 * Build inputs to handle adding a concept to the scheme or making a concept top concept in the scheme.
	 * 
	 * @return Editor_Forms_Concept
	 */
	protected function buildRelations()
	{
		/* The concepts relations is implemented but needs to be tested. Its not needed for now.
		$this->buildMultiElements(array(
				'hasTopConcept' => _('Top concepts'),
				'includeConcepts' => _('Concepts'),
		),'OpenSKOS_Form_Element_Multilink' ,array(), 'Relations with concepts', 'concept-scheme-edit-relations');
		*/
		return $this;
	}
	
	/**
	 * Gets the currently logged user's tenant.
	 *
	 * @return OpenSKOS_Db_Table_Row_Tenant
	 */
	protected function _getCurrentTenant()
	{
		if ( ! $this->_currentTenant) {
			$this->_currentTenant = OpenSKOS_Db_Table_Tenants::fromIdentity();
			if (null === $this->_currentTenant) {
				throw new Zend_Exception('Tenant not found. Needed for request to the api.');
			}
		}
	
		return $this->_currentTenant;
	}
	
	/**
	 * @return Editor_Forms_ConceptScheme
	 */
	public static function getInstance($isCreate = false)
	{
		static $instance;
	
		if (null === $instance) {
			$instance = new Editor_Forms_ConceptScheme(array('isCreate' => $isCreate));
		}
	
		return $instance;
	}
}