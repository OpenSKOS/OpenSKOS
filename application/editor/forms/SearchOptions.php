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
 *
 * @category   OpenSKOS
 * @package    OpenSKOS
 * @copyright  Copyright (c) 2015 Picturae (http://www.picturae.com)
 * @author     Picturae
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 */

class Editor_Forms_SearchOptions extends Zend_Form {

    /**
     * Holds the format in which the dates in the options must be.
     * @var string
     */
    const OPTIONS_DATE_FORMAT = 'dd/MM/yyyy';
            
    /**
     * Holds the editor options from configuration.
     *
     * @var array
     */
    protected $_editorOptions;

    /**
     * Holds the available search options.
     *
     * @var array
     */
    protected $_searchOptions;

    /**
     * Holds the currently logged user's tenant.
     *
     * @var OpenSKOS_Db_Table_Row_Tenant
     */
    protected $_currentTenant;

    /**
     * @var Editor_Models_ConceptSchemesCache
     */
    private $schemesCache;

    /**
     * @param Editor_Models_ConceptSchemesCache $schemesCache
     * @param array $options
     */
    public function __construct(Editor_Models_ConceptSchemesCache $schemesCache, $options = null)
    {
        $this->schemesCache = $schemesCache;
        parent::__construct($options);
    }

    public function init()
    {
        $this->setName('Advanced Search Options');

        $this->setMethod('Post');

        $this->_editorOptions = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getOption('editor');
        $this->_searchOptions = self::getAvailableSearchOptions();

        $this->buildSearchProfiles()
                ->buildLanguage()
                ->buildLexicalLabel()
                ->buildStatuses()
                ->buildDocProperties()
                ->buildSimpleElements()
                ->buildUserInteraction()
                ->buildCollections()
                ->buildConceptSchemes();

        if (!$this->_currentTenant->disableSearchInOtherTenants) {
            $this->buildTenants();
        }

        $this->buildButtons()
                ->buildSaveAsProfile();

        // If the user is disabled to change search profile - disable all fields.
        if (OpenSKOS_Db_Table_Users::requireFromIdentity()->disableSearchProfileChanging) {
            foreach ($this->getElements() as $element) {
                $element->setAttrib('disabled', true);
            }
        }
    }

    public function isValid($data)
    {
        // If the user is disabled to change search profile he can not change any search settings.
        if (OpenSKOS_Db_Table_Users::requireFromIdentity()->disableSearchProfileChanging) {
            $this->addError(_('You are not allowed to change your detailed search options.'));
        }

        return parent::isValid($data);
    }

    /**
     * @return Editor_Forms_SearchOptions
     */
    protected function buildSearchProfiles()
    {
        $profilesModel = new OpenSKOS_Db_Table_SearchProfiles();
        $profiles = $profilesModel->fetchAll($profilesModel->select()->where('tenant=?', $this->_getCurrentTenant()->code));
        $profilesOptions = array();
        $profilesOptions[''] = _('Default');
        foreach ($profiles as $profile) {
            $profilesOptions[$profile->id] = $profile->name;
        }
        $profilesOptions['custom'] = _('Custom');

        $this->addElement('select', 'searchProfileId', array(
            'label' => _('Search Profile'),
            'multiOptions' => $profilesOptions
        ));

        $this->addElement('text', 'searchProfileName', array(
            'filters' => array('StringTrim'),
            'label' => _('Search Profile Name'),
        ));

        $this->addElement('hidden', 'switchProfile', array('value' => 0, 'decorators' => array('ViewHelper')));

        return $this;
    }

    /**
     * @return Editor_Forms_SearchOptions
     */
    protected function buildLanguage()
    {
        if (!isset($this->_editorOptions['languages'])) {
            $this->removeElement('languages');
            return $this;
        }
        $languages = $this->_editorOptions['languages'];

        $this->addElement('multiCheckbox', 'languages', array(
            'label' => _('Language'),
            'multiOptions' => $languages
        ));
        $this->getElement('languages')->setValue(array_keys($languages));
        return $this;
    }

    /**
     * @return Editor_Forms_SearchOptions
     */
    protected function buildLexicalLabel()
    {
        $labels = array();
        if (isset($this->_searchOptions['labels'])) {
            $labels = $this->_searchOptions['labels'];
        }

        $this->addElement('multiCheckbox', 'label', array(
            'label' => _('Lexical label'),
            'multiOptions' => $labels
        ));

        if (!empty($labels)) {
            $this->getElement('label')->setValue(array_keys($labels));
        }

        return $this;
    }

    /**
     * @return Editor_Forms_SearchOptions
     */
    protected function buildStatuses()
    {
        if ($this->_getCurrentTenant()['enableStatusesSystem']) {
            $statuses = array();
            if (isset($this->_searchOptions['statuses'])) {
                $statuses = $this->_searchOptions['statuses'];
            }

            $this->addElement('multiCheckbox', 'status', array(
                'label' => _('Status'),
                'multiOptions' => $statuses
            ));

            if (!empty($statuses)) {
                $checkedOptions = array_keys($statuses);
                $this->getElement('status')->setValue($checkedOptions);
            }
        }

        $this->addElement('checkbox', 'toBeChecked', array(
            'label' => _('To be checked'),
        ));

        return $this;
    }

    /**
     * @return Editor_Forms_SearchOptions
     */
    protected function buildDocProperties()
    {
        $docProperties = array();
        if (isset($this->_searchOptions['docproperties'])) {
            $docProperties = $this->_searchOptions['docproperties'];
        }

        $this->addElement('multiCheckbox', 'properties', array(
            'label' => _('Document properties'),
            'multiOptions' => $docProperties
        ));

        if (!empty($docProperties)) {
            $this->getElement('properties')->setValue(array_keys($docProperties));
        }

        return $this;
    }

    /**
     * @return Editor_Forms_SearchOptions
     */
    protected function buildSimpleElements()
    {
        $this->addElement('checkbox', 'topConcepts', array(
            'label' => _('Only top concepts'),
        ));
        $this->addElement('checkbox', 'orphanedConcepts', array(
            'label' => _('Only orphaned concepts'),
        ));
        $this->addElement('checkbox', 'searchNotation', array(
            'label' => _('Search in notation'),
        ));
        $this->addElement('checkbox', 'searchUri', array(
            'label' => _('Search in uri'),
        ));
        return $this;
    }

    /**
     * @return Editor_Forms_SearchOptions
     */
    protected function buildUserInteraction()
    {
        $modelUsers = new OpenSKOS_Db_Table_Users();
        $users = $modelUsers->fetchAll($modelUsers->select()->where('tenant=?', $this->_getCurrentTenant()->code));
        $roles = OpenSKOS_Db_Table_Users::getUserRoles();
        $rolesOptions = array_combine($roles, $roles);
        $userData = array();
        foreach ($users as $user) {
            if (!empty($user->uri)) {
                $userData[$user->uri] = $user->name;
            } else {
                $userData[$user->getFoafPerson()->getUri()] = $user->name;
            }
        }

        $userInteractionTypes = array();
        if (isset($this->_searchOptions['interactiontypes'])) {
            $userInteractionTypes = $this->_searchOptions['interactiontypes'];
        }

        $this->addElement('hidden', 'userInteractionTypeLabel', array(
            'label' => _('Created, modified or approved'),
            'disabled' => true
        ));

        $this->addElement('multiCheckbox', 'userInteractionType', array(
            'label' => '',
            'multiOptions' => $userInteractionTypes
        ));

        $this->addElement('multiselect', 'interactionByRoles', array(
            'label' => _('Roles'),
            'multiOptions' => $rolesOptions
        ));
        $this->addElement('multiselect', 'interactionByUsers', array(
            'label' => _('Users'),
            'multiOptions' => $userData
        ));
        $this->buildDateInput('interaction');

        $this->addDisplayGroup(
                array('userInteractionType', 'interactionByRoles', 'interactionByUsers', 'interactionDateFrom', 'interactionDateTo'), 'interaction', array(
            'legend' => _('Created, modified or approved'),
            'disableDefaultDecorators' => true,
            'decorators' => array('FormElements', array('HtmlTag', array('tag' => 'div', 'id' => 'interaction'))))
        );

        return $this;
    }

    /**
     * @return Editor_Forms_SearchOptions
     */
    protected function buildDateInput($relatedElement)
    {
        $this->addElement('text', $relatedElement . 'DateFrom', array(
            'label' => _('From'),
            'size' => 10,
            'validators' => array(
                array('date', true, array(self::OPTIONS_DATE_FORMAT))),
            'class' => 'datepicker'
        ));

        $this->getElement($relatedElement . 'DateFrom')->addValidator(new OpenSKOS_Validate_DateCompare($relatedElement . 'DateTo', false));

        $this->addElement('text', $relatedElement . 'DateTo', array(
            'label' => _('To'),
            'size' => 10,
            'validators' => array(
                array('date', false, array(self::OPTIONS_DATE_FORMAT))),
            'class' => 'datepicker'
        ));

        return $this;
    }

    /**
     * @return Editor_Forms_SearchOptions
     */
    protected function buildTenants()
    {
        $modelTenants = new OpenSKOS_Db_Table_Tenants();
        $tenants = $modelTenants->fetchAll();
        $tenantsOptions = array();
        foreach ($tenants as $tenant) {
            $tenantsOptions[$tenant->code] = $tenant->name;
        }

        $this->addElement('multiselect', 'tenants', array(
            'label' => _('Tenants'),
            'multiOptions' => $tenantsOptions
        ));
        $this->getElement('tenants')->setValue(array($this->_getCurrentTenant()->code));
        return $this;
    }

    /**
     * @return Editor_Forms_SearchOptions
     */
    protected function buildCollections()
    {
        $modelCollections = new OpenSKOS_Db_Table_Sets();
        $collections = $modelCollections->fetchAll($modelCollections->select()->where('tenant = ?', $this->_getCurrentTenant()->code));
        $collectionsOptions = array();
        foreach ($collections as $collection) {
            $collectionsOptions[$collection->uri] = $collection->dc_title;
        }

        $this->addElement('multiselect', 'collections', array(
            'label' => _('Collections'),
            'multiOptions' => $collectionsOptions
        ));

        return $this;
    }

    /**
     * @return Editor_Forms_SearchOptions
     */
    protected function buildConceptSchemes()
    {
        $this->addElement('multiCheckbox', 'conceptScheme', [
            'label' => _('Concept schemes'),
            'multiOptions' => $this->schemesCache->fetchUrisCaptionsMap()
        ]);
        return $this;
    }

    /**
     * @return Editor_Forms_SearchOptions
     */
    protected function buildButtons()
    {
        $this->addElement('submit', 'ok', array(
            'label' => _('Ok')
        ));

        $this->addElement('submit', 'resetDefaults', array(
            'label' => _('Reset Defaults')
        ));

        $this->addElement('submit', 'save', array(
            'label' => _('Save Profile')
        ));

        $this->addElement('submit', 'delete', array(
            'label' => _('Delete Profile')
        ));

        return $this;
    }

    /**
     * @return Editor_Forms_SearchOptions
     */
    protected function buildSaveAsProfile()
    {
        $this->addElement('submit', 'saveAs', array(
            'label' => _('Save Profile As'),
            'decorators' => array('ViewHelper')
        ));

        $this->addElement('text', 'searchProfileNameSaveAs', array(
            'filters' => array('StringTrim'),
            'label' => 'Name',
            'decorators' => array('ViewHelper')
        ));

        $this->addDisplayGroup(
                array('saveAs', 'searchProfileNameSaveAs'), 'save-as', array('disableDefaultDecorators' => true,
            'decorators' => array('FormElements', array('HtmlTag', array('tag' => 'dd'))))
        );

        return $this;
    }

    /**
     * Gets the currently logged user's tenant.
     *
     * @return OpenSKOS_Db_Table_Row_Tenant
     */
    protected function _getCurrentTenant()
    {
        if (!$this->_currentTenant) {
            $this->_currentTenant = OpenSKOS_Db_Table_Tenants::fromIdentity();
            if (null === $this->_currentTenant) {
                throw new Zend_Exception('Tenant not found. Needed for request to the api.');
            }
        }

        return $this->_currentTenant;
    }

    /**
     * Transforms form values to options.
     * Init options which are not set.
     * Remove unneeded values.
     *
     * @param array $values
     * @return array
     */
    public static function formValues2Options($values)
    {
        // Empty value for search profile is ''
        if (empty($values['searchProfileId'])) {
            $values['searchProfileId'] = '';
        }

        // All options which are not set must be empty arrays.
        if (!isset($values['languages'])) {
            $values['languages'] = array();
        }
        if (!isset($values['label'])) {
            $values['label'] = array();
        }
        if (!isset($values['status'])) {
            $values['status'] = array();
        }
        if (!isset($values['properties'])) {
            $values['properties'] = array();
        }

        // We do not need to remember searchProfileName in options.
        if (isset($values['searchProfileName'])) {
            unset($values['searchProfileName']);
        }
        if (isset($values['searchProfileNameSaveAs'])) {
            unset($values['searchProfileNameSaveAs']);
        }
        if (isset($values['switchProfile'])) {
            unset($values['switchProfile']);
        }

        // We do not need buttons.
        if (isset($values['ok'])) {
            unset($values['ok']);
        }
        if (isset($values['resetDefaults'])) {
            unset($values['resetDefaults']);
        }
        if (isset($values['save'])) {
            unset($values['save']);
        }
        if (isset($values['delete'])) {
            unset($values['delete']);
        }
        if (isset($values['saveAs'])) {
            unset($values['saveAs']);
        }
        if (isset($values['switchProfile'])) {
            unset($values['switchProfile']);
        }

        // Some options needs to be empty arrays or strings.
        if (empty($values['userInteractionType'])) {
            $values['userInteractionType'] = array();
        }
        if (empty($values['interactionByRoles'])) {
            $values['interactionByRoles'] = array();
        }
        if (empty($values['interactionByUsers'])) {
            $values['interactionByUsers'] = array();
        }
        if (empty($values['interactionDateFrom'])) {
            $values['interactionDateFrom'] = '';
        }
        if (empty($values['interactionDateTo'])) {
            $values['interactionDateTo'] = '';
        }

        // Unset any disabled input fields.
        unset($values['userInteractionTypeLabel']);

        return $values;
    }

    /**
     * Gets an array of the available search options.
     *
     * @return array
     */
    public static function getAvailableSearchOptions()
    {
        $options['labels']['prefLabel'] = _('preferred');
        $options['labels']['altLabel'] = _('alternative');
        $options['labels']['hiddenLabel'] = _('hidden');

        $options['statuses']['none'] = _('none');

        // We can not filter by status deleted. Those concepts are not shown.
        $statuses = array_diff(
            OpenSKOS_Concept_Status::getStatuses(),
            [OpenSKOS_Concept_Status::DELETED]
        );

        foreach ($statuses as $status) {
            $options['statuses'][$status] = _($status);
        }

        $options['docproperties']['definition'] = _('definition');
        $options['docproperties']['example'] = _('example');
        $options['docproperties']['changeNote'] = _('change note');
        $options['docproperties']['editorialNote'] = _('editorial note');
        $options['docproperties']['historyNote'] = _('history note');
        $options['docproperties']['scopeNote'] = _('scope note');

        $options['interactiontypes']['created'] = _('created');
        $options['interactiontypes']['modified'] = _('modified');
        $options['interactiontypes']['approved'] = _('approved');

        return $options;
    }
    
    /**
     * Gets an array of the default search options.
     */
    public static function getDefaultSearchOptions()
    {
        // @TODO Not clean at all. Add a dependecy where default options are needed.
        $diContainer = Zend_Controller_Front::getInstance()->getDispatcher()->getContainer();
        return self::formValues2Options(
            $diContainer->get('Editor_Forms_SearchOptions')->getValues(true)
        );
    }
}
