<?php

/*
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

class Editor_SearchController extends OpenSKOS_Controller_Editor {

    /**
     * Return json search results for search in the editor with a search profile
     */
    public function indexAction()
    {
        $searchForm = Editor_Forms_Search::getInstance();

        $request = $this->getRequest();
        if (!$request->isPost()) {
            return;
        }

        if (!$searchForm->isValid($this->getRequest()->getPost())) {
            return;
        }

        $options = $this->getSearchOptions($searchForm);
        $options['sorts'] = ['sort_s_prefLabel' => 'asc'];

        /* Retrieve from Solr, to prevent heavy use of Jena */
        $options['retrieve_from_solr'] = true;

        /* @var $search \OpenSkos2\Search\Autocomplete */
        $search = $this->getDI()->get('\OpenSkos2\Search\Autocomplete');
        $concepts = $search->search($options, $numFound);

        $preview = $this->getDI()->get('Editor_Models_ConceptPreview');

        $processed_concepts = $preview->convertSolrToLinksData($concepts);

        $result = [
            'concepts' => $processed_concepts,
            'numFound' => $numFound,
            'status' => 'ok',
            'conceptSchemeOptions' => $this->_getConceptSchemeOptions(),
            'profileOptions' => $this->_getProfilesSelectOptions(),
        ];

        $response = new Zend\Diactoros\Response\JsonResponse($result);
        $this->emitResponse($response);
    }

    public function showFormAction()
    {
        $this->_helper->_layout->setLayout('editor_modal_box');

        $user = OpenSKOS_Db_Table_Users::requireFromIdentity();
        $this->view->form = $this->getSearchOptionsForm();

        $this->view->form->setAction($this->getFrontController()->getRouter()->assemble(array('controller' => 'search', 'action' => 'set-options')));

        $profilesModel = new OpenSKOS_Db_Table_SearchProfiles();

        if ((bool) $this->getRequest()->getParam('resetDefaults', false) || (bool) $this->getRequest()->getParam('switchProfile', false)) {
            // If param searchProfileId is set - loads the form for that profile.
            $profileId = $this->getRequest()->getParam('searchProfileId', '');
            if (!empty($profileId)) {
                $profile = $profilesModel->find($profileId);
                if ($profile->count() > 0) {
                    $profileSearchOptions = $profile->current()->getSearchOptions();
                    $profileSearchOptions = array_merge($profileSearchOptions, array('searchProfileId' => $profileId, 'switchProfile' => false));
                    $this->view->form->populate($profileSearchOptions);
                }
            } else {
                $this->view->form->populate(Editor_Forms_SearchOptions::getDefaultSearchOptions());
            }
        } else {
            // If the form is opened (not submited with errors) populate it with the data from the user session.
            if (!$this->getRequest()->isPost()) {
                $options = $user->getSearchOptions();
                if (empty($options)) {
                    $options = Editor_Forms_SearchOptions::getDefaultSearchOptions();
                }
                $this->view->form->populate($options);
            }
        }

        // Switch profile is set to true only from js
        $this->view->form->getElement('switchProfile')->setValue(0);

        // Check is editing and deleting of the selected profile allowed for the current user.
        $profile = $profilesModel->find($this->view->form->getElement('searchProfileId')->getValue());
        if ($profile->count() > 0) {
            if (!($user->isAllowed('editor.manage-search-profiles', null) || $user->id == $profile->current()->creatorUserId)) {
                $this->view->form->getElement('save')->setAttrib('class', 'do-not-show');
                $this->view->form->getElement('delete')->setAttrib('class', 'do-not-show');
            }
        } else {
            $this->view->form->getElement('save')->setAttrib('class', 'do-not-show');
            $this->view->form->getElement('delete')->setAttrib('class', 'do-not-show');
        }

        // Send profiles options to refresh the search profile selector.
        $this->view->assign('conceptSchemeOptions', $this->_getConceptSchemeOptions());
        $this->view->assign('profilesOptions', $this->_getProfilesSelectOptions());

        // Set concept scheme - collections map.
        $collectionsConceptSchemesMap = [];
        $conceptSchemes = $this->getDI()->get('Editor_Models_ConceptSchemesCache')->fetchAll();
        foreach ($conceptSchemes as $scheme) {
            $setUri = $scheme->getSet()[0]->getUri();
            if (!isset($collectionsConceptSchemesMap[$setUri])) {
                $collectionsConceptSchemesMap[$setUri] = [];
            }
            $collectionsConceptSchemesMap[$setUri][] = $scheme->getUri();
        }

        $this->view->assign('collectionsConceptSchemesMap', $collectionsConceptSchemesMap);
    }

    public function setOptionsAction()
    {
        $form = $this->getSearchOptionsForm();

        $request = $this->getRequest();
        if (!$request->isPost()) {
            return;
        }

        if (!$form->isValid($request->getPost())) {
            return $this->_forward('show-form');
        }

        $user = OpenSKOS_Db_Table_Users::requireFromIdentity();

        // Reset defaults
        if ((bool) $form->getValue('resetDefaults', false)) {
            $defaultProfile = $user->getFirstDefaultSearchProfile();
            if ($defaultProfile !== null) {
                return $this->_forward(
                    'show-form',
                    'search',
                    'editor',
                    array('searchProfileId' => $defaultProfile->id)
                );
            } else {
                return $this->_forward('show-form', 'search', 'editor', array('searchProfileId' => ''));
            }
        }

        // Switch profile.
        if ((bool) $form->getValue('switchProfile', false)) {
            return $this->_forward('show-form', 'search', 'editor');
        }

        // Save options or profile
        $options = Editor_Forms_SearchOptions::formValues2Options($request->getPost());

        $profilesModel = new OpenSKOS_Db_Table_SearchProfiles();

        // Save profile as new one.
        if ((bool) $form->getValue('saveAs', false)) {
            $profileName = $form->getValue('searchProfileNameSaveAs', '');
            if (empty($profileName)) {
                $form->getElement('searchProfileNameSaveAs')->addError(_('Please fill a profile name.'));
                return $this->_forward('show-form');
            }
            $newProfileId = $profilesModel->addNew($profileName, $options, $user->id, $user->tenant);

            // Switch the form to the new profile
            return $this->_forward(
                'show-form',
                'search',
                'editor',
                array('searchProfileId' => $newProfileId, 'switchProfile' => true, 'reInitForm' => true)
            );
        }

        $profileId = intval(isset($options['searchProfileId']) ? $options['searchProfileId'] : '');
        $profileId = $form->getValue('searchProfileId', false);

        if (empty($profileId) && (bool) $form->getValue('save', false)) {
            $profileName = $form->getValue('searchProfileName', '');
            if (empty($profileName)) {
                $form->getElement('searchProfileName')->addError(_('Please fill a profile name.'));
                return $this->_forward('show-form');
            }
            $newProfileId = $profilesModel->addNew($profileName, $options, $user->id, $user->tenant);

            // Switch the form to the new profile
            return $this->_forward(
                'show-form',
                'search',
                'editor',
                array('searchProfileId' => $newProfileId, 'switchProfile' => true, 'reInitForm' => true)
            );
        }
        
        $profile = null;
        if (!empty($profileId)) {
            $profile = $profilesModel->find($profileId)->current();
        }

        if ((bool) $form->getValue('save', false) || (bool) $form->getValue('delete', false)) {
            if (!empty($profileId)) {
                if (!($user->isAllowed('editor.manage-search-profiles', null) ||
                        $user->id == $profile->creatorUserId)) {
                    $form->addError(_('You are not allowed to edit that search profile.'));
                    return $this->_forward('show-form');
                }

                if ((bool) $form->getValue('save', false)) {
                    $profileName = $form->getValue('searchProfileName', '');
                    /*
                    if (empty($profileName)) {
                        $form->getElement('searchProfileName')->addError(_('Please fill a profile name.'));
                        return $this->_forward('show-form');
                    }
                    */

                    if (!empty($profileName)) {
                        $profile->name = $profileName;
                    }

                    $profile->setSearchOptions($options);
                    $profile->save();
                    return $this->_forward(
                        'show-form',
                        'search',
                        'editor',
                        array('switchProfile' => true, 'reInitForm' => true)
                    );
                }

                if ((bool) $form->getValue('delete', false)) {
                    $profile->delete();
                    return $this->_forward('show-form', 'search', 'editor', array('reInitForm' => true));
                }
            } else {
                $form->addError(_('Please choose a profile to save or delete.'));
                return $this->_forward('show-form');
            }
        }

        // Save options for the user
        if ((bool) $form->getValue('ok', false)) {
            if ($profile !== null) {
                $originalOptions = $profile->getSearchOptions();
                
                $originalOptions['searchProfileId'] = $profile->id;
            } else {
                $originalOptions = $this->getSearchOptionsForm()->getValues(true);
            }

            // Make sure that there are no any old or unneeded options in the profile.
            $originalOptions = Editor_Forms_SearchOptions::formValues2Options(
                $originalOptions
            );

            $checkOptions = Editor_Forms_SearchOptions::formValues2Options(
                array_merge($this->getSearchOptionsForm()->getValues(true), $options)
            );

            /*
            if ($checkOptions != $originalOptions) {
                $options['searchProfileId'] = 'custom';
            }
            */
            
            $user->setSearchOptions($options);
            return $this->_forward('set-options-success');
        }
    }

    public function setOptionsSuccessAction()
    {
        // Send profiles options to refresh the search profile selector.
        $this->view->assign('conceptSchemeOptions', $this->_getConceptSchemeOptions());
        $this->view->assign('profilesOptions', $this->_getProfilesSelectOptions());

        $this->_helper->_layout->setLayout('editor_modal_box');
    }

    private function _switchUserToSearchProfile(OpenSKOS_Db_Table_Row_User $user, $profileId)
    {
        //!TODO Consider movin inside the user object.
        if ($user->isAllowedToUseSearchProfile($profileId)) {
            $profile = $this->getSearchProfile($profileId);
            if (null !== $profile) {
                $detailedSearchOptions = $profile->getSearchOptions();
            } else {
                $detailedSearchOptions = $this->getSearchOptionsForm()->getValues(true);
            }
            
            $detailedSearchOptions['searchProfileId'] = $profileId;
            $user->setSearchOptions($detailedSearchOptions);
        }
    }

    private function _getConceptSchemeOptions($searchOptions = array())
    {
        $conceptSchemeOptions = array();

        $userOptions = $this->getCurrentUser()->getSearchOptions();
        $searchForm = Editor_Forms_Search::factory();

        if ($this->getCurrentUser()->disableSearchProfileChanging) {
            if (isset($searchOptions['allowedConceptScheme'])) {
                $userOptions['allowedConceptScheme'] = $searchOptions['allowedConceptScheme'];
            }

            $conceptSchemesKey = 'allowedConceptScheme';
            $conceptSchemesEl = $searchForm->getElement('allowedConceptScheme');
        } else {
            $conceptSchemesKey = 'conceptScheme';
            $conceptSchemesEl = $searchForm->getElement('conceptScheme');
        }

        $rawOptions = $conceptSchemesEl->getAttrib('options');
        foreach ($rawOptions as $id => $name) {
            $conceptSchemeOptions[] = array(
                'id' => $id,
                'name' => $name,
                'selected' => (isset($userOptions[$conceptSchemesKey]) && in_array($id, $userOptions[$conceptSchemesKey]))
            );
        }
        
        return $conceptSchemeOptions;
    }

    private function _getProfilesSelectOptions()
    {
        $profilesOptions = array();

        $userOptions = $this->getCurrentUser()->getSearchOptions();
        $searchForm = Editor_Forms_Search::factory();
        $rawOptions = $searchForm->getElement('searchProfileId')->getAttrib('options');

        foreach ($rawOptions as $id => $name) {
            $profilesOptions[] = array('id' => $id, 'name' => $name, 'selected' => (isset($userOptions['searchProfileId']) && $userOptions['searchProfileId'] == $id));
        }

        return $profilesOptions;
    }

    /**
     *
     * @todo Refactor search options
     * @param \Editor_Forms_Search $searchForm
     * @return array
     */
    private function getSearchOptions(\Editor_Forms_Search $searchForm)
    {
        $userForSearch = $searchForm->getUserForSearch();
        $loggedUser = OpenSKOS_Db_Table_Users::requireFromIdentity();

        $searchOptions = $searchForm->getValues();
        $detailedSearchOptions = $userForSearch->getSearchOptions(true);

        if ($loggedUser['id'] !== $userForSearch['id']) {

            $tenantManager = $this->getDI()->get('\OpenSkos2\TenantManager');
            $openSkos2Tenant = $tenantManager->getCachedTenant();

            return Editor_Forms_Search::mergeSearchOptions($searchOptions, $detailedSearchOptions, $openSkos2Tenant);
        }

        // Change search profile if needed and allowed. Change concept schemes if needed.
        $profileId = $this->getRequest()->getParam('searchProfileId', '');
        if (!isset($detailedSearchOptions['searchProfileId']) ||
            $detailedSearchOptions['searchProfileId'] != $profileId  ) {
            $this->_switchUserToSearchProfile($loggedUser, $profileId);
            $detailedSearchOptions = $loggedUser->getSearchOptions();
            
            if ($loggedUser->disableSearchProfileChanging) {
                $searchOptions['allowedConceptScheme'] = [];
            }
        } else {
            if (!isset($searchOptions['conceptScheme'])) {
                $searchOptions['conceptScheme'] = [];
            }
            
            if (!isset($detailedSearchOptions['conceptScheme'])) {
                $detailedSearchOptions['conceptScheme'] = [];
            }

            if ($searchOptions['conceptScheme'] != $detailedSearchOptions['conceptScheme']) {
                $detailedSearchOptions['conceptScheme'] = $searchOptions['conceptScheme'];
            }
            
            if (!isset($searchOptions['allowedConceptScheme'])) {
                $searchOptions['allowedConceptScheme'] = [];
            }
            
            if (!isset($detailedSearchOptions['allowedConceptScheme'])) {
                $detailedSearchOptions['allowedConceptScheme'] = [];
            }

            if ($searchOptions['allowedConceptScheme'] != $detailedSearchOptions['allowedConceptScheme']) {
                $detailedSearchOptions['allowedConceptScheme'] = $searchOptions['allowedConceptScheme'];
            }

            $loggedUser->setSearchOptions($detailedSearchOptions);
        }
        
        if ($loggedUser->disableSearchProfileChanging) {
            $profile = $this->getSearchProfile($detailedSearchOptions['searchProfileId']);
            $profileSearchOptions = $profile->getSearchOptions();
            
            if (empty($searchOptions['allowedConceptScheme'])) {
                $searchOptions['allowedConceptScheme'] = $profileSearchOptions['conceptScheme'];
            }
            
            $loggedUser->setSearchOptions($detailedSearchOptions);
        }

        $tenantManager = $this->getDI()->get('\OpenSkos2\TenantManager');
        $openSkos2Tenant = $tenantManager->getCachedTenant();

        return Editor_Forms_Search::mergeSearchOptions($searchOptions, $detailedSearchOptions, $openSkos2Tenant);
    }
    
    /**
     * @param string $profileId
     * @return OpenSKOS_Db_Table_Row_SearchProfile
     */
    private function getSearchProfile($profileId)
    {
        $profilesModel = new OpenSKOS_Db_Table_SearchProfiles();
        return $profilesModel->find($profileId)->current();
    }
    
    /**
     * @return \Editor_Forms_SearchOptions
     */
    private function getSearchOptionsForm()
    {
        return $this->getDI()->make('Editor_Forms_SearchOptions');
    }
}
