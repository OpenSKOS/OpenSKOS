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
* @package    OpenSKOS257
* @copyright  Copyright (c) 2011 Pictura Database Publishing. (http://www.pictura-dp.nl)
* @author     Boyan Bonev
* @license    http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
*/

class Editor_SearchController extends OpenSKOS_Controller_Editor
{
    public function indexAction()
    {	
        $this->view->searchForm = Editor_Forms_Search::getInstance();

        $request = $this->getRequest();
        if ( ! $this->getRequest()->isPost()) {
            return;
        }

        if ( ! $this->view->searchForm->isValid($this->getRequest()->getPost())) {
            return;
        }

        $userForSearch = $this->view->searchForm->getUserForSearch();		
        $loggedUser = OpenSKOS_Db_Table_Users::requireFromIdentity();

        $this->view->disableSearchProfileChanging = $loggedUser->disableSearchProfileChanging;

        $searchOptions = $this->view->searchForm->getValues();
        $detailedSearchOptions = $userForSearch->getSearchOptions(true);

        // Change search profile if needed and allowed. Change concept schemes if needed.
        //!TODO Refactor. That code evolved little ugly.
        if ($loggedUser['id'] == $userForSearch['id']) {
            $profileId = $this->getRequest()->getParam('searchProfileId', '');
            
            if ($detailedSearchOptions['searchProfileId'] != $profileId 
                || ! isset($detailedSearchOptions['searchProfileId'])) {
                
               $this->_switchUserToSearchProfile($loggedUser, $profileId);
               $detailedSearchOptions = $loggedUser->getSearchOptions();
               
               // Reset allowedConceptScheme
               if ($loggedUser->disableSearchProfileChanging) {
                   $searchOptions['allowedConceptScheme'] = array();
               }

            } elseif ((!isset($searchOptions['conceptScheme']) || !isset($detailedSearchOptions['conceptScheme'])) 
                || $searchOptions['conceptScheme'] != $detailedSearchOptions['conceptScheme']) {

                if ($loggedUser->isAllowedToUseSearchProfile('custom')) {
                    // Change concept schemes selection
                    $detailedSearchOptions['searchProfileId'] = 'custom';
                    if (isset($searchOptions['conceptScheme'])) {
                        $detailedSearchOptions['conceptScheme'] = $searchOptions['conceptScheme'];
                    } else {
                        $detailedSearchOptions['conceptScheme'] = array();
                    }

                    $loggedUser->setSearchOptions($detailedSearchOptions);
                }
            }
        }

        // Select the concepts
        $apiClient = new Editor_Models_ApiClient();
        try {
            $conceptsRaw = $apiClient->searchConcepts(
                Editor_Forms_Search::mergeSearchOptions($searchOptions, $detailedSearchOptions)
            );
        } catch (Exception $ex) {
            $this->getHelper('json')->sendJson(array('status' => 'error', 'message' => 'Bad query syntax.'));			
        }

        $concepts = array();
        foreach ($conceptsRaw['data'] as $concept) {
            $concepts[] = $concept->toArray(array('uuid', 'uri', 'status', 'schemes', 'previewLabel', 'previewScopeNote'));
        }

        $this->getHelper('json')->sendJson(
            array(
                'status' => 'ok',
                'numFound' => $conceptsRaw['numFound'],
                'concepts' => $concepts,
                'conceptSchemeOptions' => $this->_getConceptSchemeOptions($searchOptions),
                'profileOptions' => $this->_getProfilesSelectOptions()
            )
        );
    }

    public function showFormAction()
    {
        $this->_helper->_layout->setLayout('editor_modal_box');

        $user = OpenSKOS_Db_Table_Users::requireFromIdentity();

        if ((bool)$this->getRequest()->getParam('reInitForm', false)) {
            $this->view->form = new Editor_Forms_SearchOptions();
        } else {
            $this->view->form = Editor_Forms_SearchOptions::getInstance();
        }

        $this->view->form->setAction($this->getFrontController()->getRouter()->assemble(array('controller'=>'search', 'action' => 'set-options')));

        $profilesModel = new OpenSKOS_Db_Table_SearchProfiles();

        if ((bool)$this->getRequest()->getParam('resetDefaults', false) || (bool)$this->getRequest()->getParam('switchProfile', false)) {
            // If param searchProfileId is set - loads the form for that profile.
            $profileId = $this->getRequest()->getParam('searchProfileId', '');
            if ( ! empty($profileId)) {
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
            if ( ! $this->getRequest()->isPost()) {
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
            if ( ! ($user->isAllowed('editor.manage-search-profiles', null) 
                    || $user->id == $profile->current()->creatorUserId)) {

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
        $apiClient = new Editor_Models_ApiClient();
        $conceptSchemes = $apiClient->getConceptSchemeMap('uri', 'collection');
        $collectionsConceptSchemesMap = array_fill_keys(array_values($conceptSchemes), array());
        foreach ($conceptSchemes as $conceptSchemeUri => $conceptSchemeCollection) {
            $collectionsConceptSchemesMap[$conceptSchemeCollection][] = $conceptSchemeUri;
        }

        $this->view->assign('collectionsConceptSchemesMap', $collectionsConceptSchemesMap);
    }

    public function setOptionsAction()
    {
        $form = Editor_Forms_SearchOptions::getInstance();

        $request = $this->getRequest();
        if ( ! $this->getRequest()->isPost()) {
            return;
        }

        if ( ! $form->isValid($this->getRequest()->getPost())) {
            return $this->_forward('show-form');
        }

        $user = OpenSKOS_Db_Table_Users::requireFromIdentity();

        // Reset defaults
        if ((bool)$this->getRequest()->getParam('resetDefaults', false)) {
            $defaultProfile = $user->getFirstDefaultSearchProfile();
            if ($defaultProfile !== null) {
                return $this->_forward('show-form', 'search', 'editor', array('searchProfileId' => $defaultProfile->id));
            } else {
                return $this->_forward('show-form', 'search', 'editor', array('searchProfileId' => ''));
            }
        }

        // Switch profile.
        if ((bool)$this->getRequest()->getParam('switchProfile', false)) {
            return $this->_forward('show-form', 'search', 'editor');
        }

        // Save options or profile
        $options = Editor_Forms_SearchOptions::formValues2Options($this->getRequest()->getPost());

        $profilesModel = new OpenSKOS_Db_Table_SearchProfiles();

        // Save profile as new one.
        if ((bool)$this->getRequest()->getParam('saveAs', false)) {
            $profileName = $this->getRequest()->getParam('searchProfileNameSaveAs', '');
            if (empty($profileName)) {
                $form->getElement('searchProfileNameSaveAs')->addError(_('Please fill a profile name.'));
                return $this->_forward('show-form');
            }
            $newProfileId = $profilesModel->addNew($profileName, $options, $user->id, $user->tenant);

            // Switch the form to the new profile
            return $this->_forward('show-form', 'search', 'editor', array('searchProfileId' => $newProfileId, 'switchProfile' => true, 'reInitForm' => true));
        }

        // Save or delete existing profile.
        $profileId = intval($this->getRequest()->getParam('searchProfileId', ''));
        $profile = $profilesModel->find($profileId)->current();
        if (((bool)$this->getRequest()->getParam('save', false) || (bool)$this->getRequest()->getParam('delete', false)) &&  ! empty($profileId)) {
            if ( ! ($user->isAllowed('editor.manage-search-profiles', null) || $user->id == $profile->creatorUserId)) {
                $form->addError(_('You are not allowed to edit that search profile.'));
                return $this->_forward('show-form');
            }

            if ((bool)$this->getRequest()->getParam('save', false)) {				
                $profileName = $this->getRequest()->getParam('searchProfileName', '');
                if (empty($profileName)) {
                        $form->getElement('searchProfileName')->addError(_('Please fill a profile name.'));
                        return $this->_forward('show-form');
                }

                $profile->name = $profileName;
                $profile->setSearchOptions($options);
                $profile->save();
                return $this->_forward('show-form', 'search', 'editor', array('switchProfile' => true, 'reInitForm' => true));
            }

            if ((bool)$this->getRequest()->getParam('delete', false)) {
                $profile->delete();
                return $this->_forward('show-form', 'search', 'editor', array('reInitForm' => true));
            }
        }

        // Save options for the user
        if ((bool)$this->getRequest()->getParam('ok', false)) {
            if (null !== $profile) {
                $originalOptions = $profile->getSearchOptions();
                $originalOptions = Editor_Forms_SearchOptions::formValues2Options($originalOptions); // Make sure that there are no any old or unneeded options in the profile.
                $originalOptions['searchProfileId'] = $profile->id;
            } else {
                $originalOptions = Editor_Forms_SearchOptions::getDefaultSearchOptions();
            }

            $checkOptions = array_merge(Editor_Forms_SearchOptions::getDefaultSearchOptions(), $options);

            if ($checkOptions != $originalOptions) {
                $options['searchProfileId'] = 'custom';
            }

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
            $profilesModel = new OpenSKOS_Db_Table_SearchProfiles();
            $profile = $profilesModel->find($profileId)->current();
            if (null !== $profile) {
                 $detailedSearchOptions = $profile->getSearchOptions();
            } else {
                 $detailedSearchOptions = Editor_Forms_SearchOptions::getDefaultSearchOptions();
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
}
