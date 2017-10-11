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
 * @copyright  Copyright (c) 2011 Pictura Database Publishing. (http://www.pictura-dp.nl)
 * @author     Boyan Bonev
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 */
use OpenSkos2\Concept;
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Namespaces\Dc;
use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Rdf\Uri;
use OpenSkos2\Rdf\Literal;
use OpenSkos2\Validator\Resource as ResourceValidator;
use OpenSkos2\SkosXl\LabelManager;
use OpenSkos2\Exception\ResourceNotFoundException;
use Zend\Diactoros\Response\JsonResponse;

class Editor_ConceptController extends OpenSKOS_Controller_Editor
{

    public function indexAction()
    {
        $this->_forward('view');
    }

    public function createAction()
    {
        $this->_requireAccess('editor.concepts', 'propose', self::RESPONSE_TYPE_PARTIAL_HTML);
        $this->_helper->_layout->setLayout('editor_central_content');
        $form = Editor_Forms_Concept::getInstance(null, $this->getOpenSkos2Tenant());
        
        $labelHelper = $this->getDI()->get('\OpenSkos2\Concept\LabelHelper');
        
        $form->populate(
            Editor_Forms_Concept_ConceptToForm::getNewConceptFormData(
                $this->getInitialLanguage(),
                $this->getRequest()->getParam('label'),
                $this->getOpenSkos2Tenant(),
                $labelHelper
            )
        );
        $this->view->form = $form->setAction(
            $this->getFrontController()->getRouter()->assemble(array('controller' => 'concept', 'action' => 'save'))
        );
    }

    public function editAction()
    {
        $this->_helper->_layout->setLayout('editor_central_content');

        $concept = $this->_getConcept();

        $form = Editor_Forms_Concept::getInstance($concept);

        if ($form->getIsCreate()) {
            $this->_requireAccess('editor.concepts', 'propose', self::RESPONSE_TYPE_PARTIAL_HTML);
        } else {
            $this->_requireAccess('editor.concepts', 'edit', self::RESPONSE_TYPE_PARTIAL_HTML);
        }

        $this->checkConceptTenantForEdit($concept);

        if ($this->getRequest()->isPost()) {
// If we are here after post - there are errors in the form.
            $this->view->errors = $this->_getParam('errors', array());

            if ($form->getIsCreate()) {
                $concept = new Concept();
            }

// Populate the concept with posted data.
            Editor_Forms_Concept_FormToConcept::toConcept(
                $concept, $this->getRequest()->getPost(), $this->getDI()->get('\OpenSkos2\ConceptSchemeManager'), OpenSKOS_Db_Table_Users::fromIdentity(), $this->getDI()->get('\OpenSkos2\PersonManager')
            );
        }

        $form->reset();
        $form->populate(
            Editor_Forms_Concept_ConceptToForm::toFormData($concept)
        );

        $form->setAction(
            $this->getFrontController()->getRouter()->assemble(['controller' => 'concept', 'action' => 'save'])
        );
        $this->view->form = $form;

        $this->view->assign('footerData', $this->_generateFooter($concept));
    }

    public function saveAction()
    {
        $concept = $this->_getConcept();
        
        $form = Editor_Forms_Concept::getInstance($concept, $this->getOpenSkos2Tenant());
        
        if ($form->getIsCreate()) {
            $this->_requireAccess('editor.concepts', 'propose', self::RESPONSE_TYPE_PARTIAL_HTML);
        } else {
            $this->_requireAccess('editor.concepts', 'edit', self::RESPONSE_TYPE_PARTIAL_HTML);
        }

        $this->checkConceptTenantForEdit($concept);

        $params = $this->getRequest()->getParams();
        if (!$form->isValid($params)) {
            return $this->_forward('edit');
        }

        $form->populate($params);

        if ($form->getIsCreate()) {
            $concept = new Concept();
        }

        Editor_Forms_Concept_FormToConcept::toConcept(
            $concept, $form->getValues(), $this->getDI()->get('\OpenSkos2\ConceptSchemeManager'), OpenSKOS_Db_Table_Users::fromIdentity(), $this->getDI()->get('\OpenSkos2\PersonManager')
        );

        $validator = new ResourceValidator(
            
            $this->getConceptManager(),
            $this->getOpenSkos2Tenant()
            
        );

        if ($validator->validate($concept)) {
            if ($form->getIsCreate()) {

                $concept->selfGenerateUri(
                    $this->getOpenSkos2Tenant(),
                    $this->getConceptManager()
                );
            }

            $this->handleStatusAutomatedActions($concept, $form->getValues());

            $this->getConceptManager()->replaceAndCleanRelations($concept);
        } else {
            return $this->_forward('edit', 'concept', 'editor', array('errors' => $validator->getErrorMessages()));
        }

        $this->_helper->redirector('view', 'concept', 'editor', array('uri' => $concept->getUri()));
    }

    public function viewAction()
    {
        $this->_helper->_layout->setLayout('editor_central_content');

        try {
            $concept = $this->_getConcept();
        } catch (ResourceNotFoundException $e) {
            $this->view->assign('errorMessage', $e->getMessage());
            return null;
        }

        $user = OpenSKOS_Db_Table_Users::fromIdentity();
        if (!empty($user)) {
            $user->updateUserHistory($concept->getUri());
        }
        
        $this->view->assign('currentConcept', $concept);
        $this->view->assign('personManager', $this->getDI()->get('\OpenSkos2\PersonManager'));
        $this->view->assign('conceptManager', $this->getConceptManager());
        $this->view->assign('conceptSchemes', $this->getDI()->get('Editor_Models_ConceptSchemesCache')->fetchAll());
        $this->view->assign('footerData', $this->_generateFooter($concept));
        $this->view->assign('tenant', $this->getOpenSkos2Tenant());
    }

    public function deleteAction()
    {
        $this->_requireAccess('editor.concepts', 'delete', self::RESPONSE_TYPE_JSON);

        $concept = $this->_getConcept();

        if (!$concept->hasAnyRelations()) {
            $this->getConceptManager()->deleteSoft(
                $concept, $this->getCurrentUser()->getFoafPerson()
            );

            $user = OpenSKOS_Db_Table_Users::fromIdentity();
            if (!empty($user)) {
                $user->removeFromUserHistory($concept->getUri());
            }

            $this->getHelper('json')->sendJson(array('status' => 'ok'));
        } else {
            $this->getHelper('json')->sendJson(array('status' => 'error', 'message' => _('A concept can not be deleted while there are semantic relations or mapping properties associated with it.')));
        }
    }

    public function getNarrowerRelationsAction()
    {
        $data = array();

        $narrowers = $this->getConceptManager()->fetchRelations(
            $this->_getConcept()->getUri(), Skos::NARROWER
        );

        $preview = $this->getDI()->get('Editor_Models_ConceptPreview');

        $this->emitResponse(
            new JsonResponse([
            'status' => 'ok',
            'result' => $preview->convertToLinksData($narrowers),
            ])
        );
    }

    public function exportAction()
    {
        if (!$this->getRequest()->isPost()) {
            return $this->_forward('index', 'index');
        }

        $user = $this->getCurrentUser();

        $export = new Editor_Models_Export();

        $export->set('userId', $user['id']);
        $export->set('format', $this->getRequest()->getPost('format'));
        $export->set('type', $this->getRequest()->getPost('type'));
        $export->set('maxDepth', $this->getRequest()->getPost('maxDepth')); // Currently this applies only for rtf export.

        $outputFileName = $this->getRequest()->getPost('fileName');
        if (empty($outputFileName)) {
            $export->set('outputFileName', uniqid());
        } else {
            $export->set('outputFileName', $outputFileName);
        }

        $fieldsToExport = $this->getRequest()->getPost('fieldsToExport');
        if (empty($fieldsToExport)) {
            $export->set('fieldsToExport', array());
        } else {
            $export->set('fieldsToExport', explode(',', $this->getRequest()->getPost('fieldsToExport')));
        }

        switch ($export->get('type')) {
            case 'concept':
// We have the uri in additionalData.
                $export->set('uris', [$this->getRequest()->getPost('additionalData')]);
                break;
            case 'history':
                $export->set('uris', $user->getUserHistoryUris());
                break;
            case 'selection':
                $export->set('uris', $user->getConceptsSelectionUris());
                break;
            case 'search':
// We have the json encoded search form data in additionalData.
                $searchFormData = Zend_Json::decode(
                        $this->getRequest()->getPost('additionalData'), Zend_Json::TYPE_ARRAY
                );

                $searchFormData = $this->_fixJsSerializedArrayData('conceptScheme', $searchFormData);
                $searchFormData = $this->_fixJsSerializedArrayData('allowedConceptScheme', $searchFormData);

                $userForSearch = OpenSKOS_Db_Table_Users::requireById($searchFormData['user']);
                $userSearchOptions = $userForSearch->getSearchOptions($user['id'] != $userForSearch['id']);
                $userSearchOptions['sorts'] = ['sort_s_prefLabel' => 'asc'];
                $export->set(
                    'searchOptions', Editor_Forms_Search::mergeSearchOptions($searchFormData, $userSearchOptions)
                );
                break;
        }

        if ($export->isTimeConsumingExport()) {
            $export->exportWithBackgroundJob();
            $this->_redirect($this->getRequest()->getPost('currentUrl'));
        } else {
            $fileContent = $export->exportToString();
            $fileDetails = $export->getExportFileDetails();
            $this->getHelper('file')->sendFileContent($fileDetails['fileName'], $fileContent, $fileDetails['mimeType']);
        }
    }

    /**
     * Checks does a concept with the same pref label exist.
     * 
     */
    public function checkPrefLabelAction()
    {
        $doExist = $this->getConceptManager()->askForPrefLabel(
            $this->getRequest()->getPost('prefLabel')
        );

        $this->getHelper('json')->sendJson(['status' => 'ok', 'result' => ['doExist' => $doExist]]);
    }

    /**
     * Changes the status of all concepts that are in the users selection.
     * 
     */
    public function changeSelectionStatusAction()
    {
        $this->_requireAccess('editor.concepts', 'edit', self::RESPONSE_TYPE_JSON);

        $status = $this->getRequest()->getPost('status');
        if (!empty($status)) {
            $user = $this->getCurrentUser();
            $concepts = $user->getConceptsSelection();

            $this->getConceptManager()->setIsNoCommitMode(true);

            /* @var $concept \OpenSkos2\Concept */
            foreach ($concepts as $key => $concept) {
// It is not allowed to edit concepts of different tenants.
                if ($concept->getTenant() != $user->tenant) {
                    continue;
                }

                $currentStatus = $concept->getStatus();
                $person = $user->getFoafPerson();

                $concept->setModified($person);
                $concept->setProperty(OpenSkos::STATUS, new Literal($status));
                $concept->handleStatusChange($person, $currentStatus);

                $this->getConceptManager()->replace($concept);
            }

            $this->getConceptManager()->commit();
            $this->getConceptManager()->setIsNoCommitMode(false);
        }

        $this->getHelper('json')->sendJson(array('status' => 'ok'));
    }

    /**
     * @return OpenSkos2\Concept
     * @throws ResourceNotFoundException
     */
    protected function _getConcept()
    {
        $uri = $this->getRequest()->getParam('uri');
        if (!empty($uri)) {
            $concept = $this->getConceptManager()->fetchByUri($uri);

//!TODO Handle deleted all around the system.
            if ($concept->isDeleted()) {
                throw new ResourceNotFoundException('The concpet was not found (it is deleted).');
            }

            return $concept;
        } else {
            return null;
        }
    }

    /**
     * @FIXME There is too much logic data in the view, it should be moved to helpers and loaded before rendering.
     * A lot of the data should be loaded into the view from the controller.
     */
    protected function _generateFooter(OpenSkos2\Concept &$concept)
    {
        $footerData = [];
        $footerFields = [
            'created' => [
                'user' => [DcTerms::CREATOR, Dc::CREATOR],
                'date' => DcTerms::DATESUBMITTED,
            ],
            'modified' => [
                'user' => [OpenSkos::MODIFIEDBY],
                'date' => DcTerms::MODIFIED,
            ],
            'approved' => [
                'user' => [OpenSkos::ACCEPTEDBY],
                'date' => DcTerms::DATEACCEPTED,
            ],
        ];

        $personManager = $this->getDI()->get('\OpenSkos2\PersonManager');

        foreach ($footerFields as $field => $properties) {
            $usersNames = [];
            $dates = [];

            foreach ($properties['user'] as $userProperty) {
                if (!$concept->isPropertyEmpty($userProperty)) {
                    foreach ($concept->getProperty($userProperty) as $user) {
                        if ($user instanceof Uri && $personManager->askForUri($user)) {
                            $usersNames[] = $personManager->fetchByUri($user)->getCaption();
                        } elseif ($user instanceof Uri) {
                            $usersNames[] = $user->getUri();
                        } else {
                            $usersNames[] = $user->getValue();
                        }
                    }
                }
            }

            if (!$concept->isPropertyEmpty($properties['date'])) {
                foreach ($concept->getProperty($properties['date']) as $date) {
// @TODO Always have date time or string as value
                    if ($date->getValue() instanceof \DateTime) {
                        $dates[] = $date->getValue()
// @TODO there is a timezone already. Check that
                            ->setTimezone(new DateTimeZone(ini_get('date.timezone')))
                            ->format('d-m-Y H:i:s');
                    } else {
                        $dates[] = date('d-m-Y H:i:s', strtotime($date->getValue()));
                    }
                }
            }

            $footerData[$field]['user'] = !empty($usersNames) ? implode('<br />', $usersNames) : 'N/A';
            $footerData[$field]['date'] = !empty($dates) ? implode('<br />', $dates) : 'N/A';
        }

        return $footerData;
    }

    /**
     * Check if the user's tenant and the concept's tenant are the same. 
     * If not - do not allow edit and return to view with error.
     * 
     * @param $concept OpenSkos2\Concept
     */
    protected function checkConceptTenantForEdit($concept)
    {
        if (null !== $concept) {
            if ($concept->getTenant() != $this->getCurrentUser()->tenant) {
                $this->getHelper('FlashMessenger')
                    ->setNamespace('error')
                    ->addMessage(_('You can not edit concepts of different tenants.'));
                $this->_helper->redirector(
                    'view', 'concept', 'editor', ['uri' => $this->getRequest()->getParam('uri')]
                );
            }
        }
    }

    /**
     * Fix key[] which is not serialized correctly.
     * @param array $key
     * @param array $data
     */
    protected function _fixJsSerializedArrayData($key, $data)
    {
        if (isset($data[$key . '[]'])) {
            if (is_array($data[$key . '[]'])) {
                $data[$key] = $data[$key . '[]'];
            } else {
                $data[$key] = array($data[$key . '[]']);
            }
            unset($data[$key . '[]']);
        }
        return $data;
    }

    /**
     * Handles some automated actions for when status is changed.
     * @param Concept $concept
     * @param array $formData
     */
    protected function handleStatusAutomatedActions(Concept $concept, $formData)
    {
        if (!empty($formData['statusOtherConcept'])) {

            if ($this->getConceptManager()->askForUri($formData['statusOtherConcept'])) {
                $otherConcept = $this->getConceptManager()->fetchByUri($formData['statusOtherConcept']);

                if ($concept->getStatus() == Concept::STATUS_REDIRECTED ||
                    $concept->getStatus() == Concept::STATUS_OBSOLETE) {

                    foreach ($concept->retrieveLanguages() as $lang) {
                        $concept->addUniqueProperty(
                            Skos::CHANGENOTE, new Literal(_('Forward') . ': ' . $otherConcept->getUri(), $lang)
                        );
                    }
                }

                if ($concept->getStatus() == Concept::STATUS_REDIRECTED) {
                    foreach ($concept->retrieveLanguages() as $lang) {
                        if ($concept->hasPropertyInLanguage(Skos::PREFLABEL, $lang)) {
                            $otherConcept->addUniqueProperty(
                                $formData['statusOtherConceptLabelToFill'], $concept->retrievePropertyInLanguage(Skos::PREFLABEL, $lang)[0]
                            );
                        }
                    }

                    $this->getConceptManager()->replace($otherConcept);
                }
            }
        }
    }

    /**
     * @return OpenSkos2\ConceptManager
     */
    protected function getConceptManager()
    {
        return $this->getDI()->get('OpenSkos2\ConceptManager');
    }


    /**
     * Checks if the browser language is supported and returns it. If not supported - gets the first one.
     * @return string
     */
    protected function getInitialLanguage()
    {
        $initialLanguage = Zend_Registry::get('Zend_Locale')->getLanguage();
        $editorOptions = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getOption('editor');
        if (!empty($editorOptions['languages']) && !in_array($initialLanguage, $editorOptions['languages'])) {
// If the browser language is supported
            $initialLanguage = key($editorOptions['languages']);
        }
        return $initialLanguage;
    }

}
