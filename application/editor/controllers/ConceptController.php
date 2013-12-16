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
		
		$notation = OpenSKOS_Db_Table_Notations::getNext();
		
		$initialLanguage = Zend_Registry::get('Zend_Locale')->getLanguage();		
		$editorOptions = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getOption('editor');
		if (! empty($editorOptions['languages']) && ! in_array($initialLanguage, $editorOptions['languages'])) { // If the browser language is supported
			$initialLanguage = key($editorOptions['languages']);
		}
		
		$concept = new Editor_Models_Concept(new Api_Models_Concept(array(
				'prefLabel@'.$initialLanguage => array($this->getRequest()->getParam('label')),
				'notation' => array($notation)
		)));
		
		$form = Editor_Forms_Concept::getInstance(true);
		$formData = $concept->toForm();
		$form->getElement('conceptSchemeSelect')-> setMultiOptions($formData['conceptSchemeSelect']);
		$form->populate($formData);
		$this->view->form = $form->setAction($this->getFrontController()->getRouter()->assemble ( array ('controller'=>'concept', 'action' => 'save')));
	}
	
	public function editAction()
	{	
		$this->_helper->_layout->setLayout('editor_central_content');
		
		$concept = $this->_getConcept();
		
		if (null === $concept) {
			$this->_requireAccess('editor.concepts', 'propose', self::RESPONSE_TYPE_PARTIAL_HTML);
		} else {
			$this->_requireAccess('editor.concepts', 'edit', self::RESPONSE_TYPE_PARTIAL_HTML);
		}
		
		$this->_checkConceptTenantForEdit($concept);
		
		$form = Editor_Forms_Concept::getInstance(null === $concept);
		
		if ( ! $this->getRequest()->isPost()) {
			$formData = $concept->toForm();
		} else {
			// If we are here after post - there are errors in the form.
			$this->view->errors = $this->_getParam('errors', array());
			
			$formData = $this->getRequest()->getPost();
				
			if ($form->getIsCreate()) {
				$concept = new Editor_Models_Concept(new Api_Models_Concept());				
			}
			
			$extraData = $concept->transformFormData($formData);
			$concept->setConceptData($formData);			
			$formData = $concept->toForm();			
			$formData = array_merge($formData, $extraData);
			
			$formData['notation'] = $this->getRequest()->getPost('notation');
			$formData['uri'] = $this->getRequest()->getPost('uri');
			if ($form->getIsCreate()) {				
				$formData['baseUri'] = $this->getRequest()->getPost('baseUri');
			}
		}
		
		
		
		$form->reset();
		$form->populate($formData);
		
		if (isset($formData['topConceptOf'])) {
		
			//extract checked
			$values = array();
			foreach ($formData['topConceptOf'] as $uuid => $checked) {
				if ($checked) {
					$values[] = $uuid;
					$formData['topConceptOf'][$uuid] = false;
				}
			}
		
			//set all options
			$form->getElement('topConceptOf')->setMultiOptions($formData['topConceptOf']);
		
			//set checked options
			$form->getElement('topConceptOf')->setValue($values);
		}
		$form->getElement('conceptSchemeSelect')->setMultiOptions($formData['conceptSchemeSelect']);
		unset($formData['topConceptOf']);

		$form->setAction($this->getFrontController()->getRouter()->assemble ( array ('controller'=>'concept', 'action' => 'save')));
		$this->view->form = $form;
		
		$this->view->assign('footerData', $this->_generateFooter($concept));
	}
		
	public function saveAction()
	{
		$concept = $this->_getConcept();
		
		$form = Editor_Forms_Concept::getInstance(null === $concept);
		
		$formData = $this->getRequest()->getParams();

		if (!$this->getRequest()->isPost()) {
			$this->getHelper('FlashMessenger')->setNamespace('error')->addMessage(_('No POST data recieved'));
			$this->_helper->redirector('edit');
		}
			
		$this->_checkConceptTenantForEdit($concept);
		
		if (!$form->isValid($formData)) {
			return $this->_forward('edit');
		} else {
			
			//@FIXME should upgrade multi hidden fields to allow easy submission (change name to template something)
			array_shift($formData['inScheme']);
			
			$form->populate($formData);
			
			if (null === $concept) {
				$this->_requireAccess('editor.concepts', 'propose', self::RESPONSE_TYPE_PARTIAL_HTML);
				$concept = new Editor_Models_Concept(new Api_Models_Concept());
			} else {
				$this->_requireAccess('editor.concepts', 'edit', self::RESPONSE_TYPE_PARTIAL_HTML);
			}
			
			$formData = $form->getValues();
			
			$oldData = $concept->getData();
			
			//by reference.
			$extraData = $concept->transformFormData($formData);
			$concept->setConceptData($formData, $extraData);
			
			try {
				
				$user = OpenSKOS_Db_Table_Users::fromIdentity();
				
				$extraData = array_merge($extraData, array(
						'tenant' => $user->tenant,
						'modified_by' => (int)$user->id,
						'modified_timestamp' =>  date("Y-m-d\TH:i:s\Z"),
						'toBeChecked' => (isset($extraData['toBeChecked']) ? (bool)$extraData['toBeChecked'] : false))
				);
				
				if ( ! isset($extraData['uuid']) || empty($extraData['uuid'])) {					
					$extraData['uuid'] = $concept['uuid'];
					$extraData['created_by'] = $extraData['modified_by'];
					$extraData['created_timestamp'] = $extraData['modified_timestamp'];					
				} else {
					if (isset($oldData['created_by'])) {
						$extraData['created_by'] = $oldData['created_by'];
					}
					if (isset($oldData['created_timestamp'])) {
						$extraData['created_timestamp'] = $oldData['created_timestamp'];
					}					
					if (isset($oldData['collection'])) {
						$extraData['collection'] = $oldData['collection'];
					}
					if (isset($oldData['approved_by'])) {
						$extraData['approved_by'] = $oldData['approved_by'];
					}
					if (isset($oldData['approved_timestamp'])) {
						$extraData['approved_timestamp'] = $oldData['approved_timestamp'];
					}
					if (isset($oldData['deleted_by'])) {
						$extraData['deleted_by'] = $oldData['deleted_by'];
					}
					if (isset($oldData['deleted_timestamp'])) {
						$extraData['deleted_timestamp'] = $oldData['deleted_timestamp'];
					}
				}
				
				if ($extraData['status'] === 'approved' && $oldData['status'] !== 'approved') {
					$extraData['approved_timestamp'] = $extraData['modified_timestamp'];
					$extraData['approved_by'] = $extraData['modified_by'];
				}
				
				if ($extraData['status'] !== 'approved') {
					$formData['approved_by'] = '';
					$formData['approved_timestamp'] = '';
					$extraData['approved_by'] = '';
					$extraData['approved_timestamp'] = '';
				}
				
				if ($extraData['status'] !== 'expired') {
					$formData['deleted_by'] = '';
					$formData['deleted_timestamp'] = '';
					$extraData['deleted_by'] = '';
					$extraData['deleted_timestamp'] = '';
				}
				
				if ( ! isset($extraData['collection'])) {
					if (isset($concept['inScheme']) && isset($concept['inScheme'][0])) {
						$firstConceptScheme = Editor_Models_ApiClient::factory()->getConceptSchemes($concept['inScheme'][0]);
						$firstConceptScheme = array_shift($firstConceptScheme);
						if ( ! empty($firstConceptScheme) && isset($firstConceptScheme['collection'])) {
							$extraData['collection'] = $firstConceptScheme['collection'];
						}
					}
				}
				
				$concept->setConceptData($formData, $extraData);

				if ($concept->save($extraData)) {
					if (!isset($concept['inScheme'])) {
						$newSchemes = array();
					} else {
						$newSchemes = $concept['inScheme'];
					}
					
					if (!isset($oldData['inScheme'])) {
						$oldSchemes = array();
					} else {
						$oldSchemes = $oldData['inScheme'];
					}
					
					$concept->updateConceptSchemes($newSchemes, $oldSchemes);
				} else {
					return $this->_forward('edit', 'concept', 'editor', array('errors' => $concept->getErrors()));
				}
			} catch (Zend_Exception $e) {
				return $this->_forward('edit', 'concept', 'editor', array('errors' => array(new Editor_Models_ConceptValidator_Error('unknown', $e->getMessage()))));
			}
			$this->_helper->redirector('view',
					'concept',
					'editor',
					array('uuid' => $extraData['uuid']));
		}
	}
	
	public function viewAction()
	{
		try {
			$this->_helper->_layout->setLayout('editor_central_content');
			$user = OpenSKOS_Db_Table_Users::fromIdentity();
				
			$apiClient = new Editor_Models_ApiClient();
			$concept = $this->_getConcept();
			$conceptSchemes = $apiClient->getConceptSchemeUriMap(null, $concept['tenant']);
			$currentConceptSchemes = $concept->getConceptSchemes();
				
			if (null !== $user)
				$user->updateUserHistory($concept['uuid']);
				
			$this->view->assign('currentConcept', $concept);
			$this->view->assign('conceptLanguages', $concept->getConceptLanguages());
			$this->view->assign('conceptSchemes', $conceptSchemes);
	
			$this->view->assign('footerData', $this->_generateFooter($concept));
				
			if (isset($currentConceptSchemes['inScheme'])) {
				$this->view->assign('schemeUris', $currentConceptSchemes['inScheme']);
			}
		} catch (Zend_Exception $e) {
			$this->view->assign('errorMessage', $e->getMessage());
		}
	}
	
	public function deleteAction()
	{
		$this->_requireAccess('editor.concepts', 'delete', self::RESPONSE_TYPE_JSON);
		
		$concept = $this->_getConcept();
		
		if ( ! $concept->hasAnyRelations()) {
			$concept->delete(true);
			$this->getHelper('json')->sendJson(array('status' => 'ok'));
		} else {
			$this->getHelper('json')->sendJson(array('status' => 'error', 'message' => _('A concept can not be deleted while there are semantic relations or mapping properties associated with it.')));
		}
	}

	public function getNarrowerRelationsAction()
	{
		$data = array();
		$conceptRaw = Api_Models_Concepts::factory()->getConcept($this->getRequest()->getParam('uuid'));
		if (null !== $conceptRaw) {
			$concept = new Editor_Models_Concept($conceptRaw);
			$relations = $concept->getNarrowers();
			foreach ($relations as $relation) {
				$data[] = $relation->toArray(array('uuid', 'uri', 'status', 'schemes', 'previewLabel', 'previewScopeNote'));
			}
		}
		$this->getHelper('json')->sendJson(array('status' => 'ok', 'result' => $data));
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
			case 'concept' : {
				$export->set('conceptUuid', $this->getRequest()->getPost('additionalData')); // We have the uuid in additionalData.				
			} break;
			case 'search' : {
				$searchFormData = Zend_Json::decode($this->getRequest()->getPost('additionalData'), Zend_Json::TYPE_ARRAY); // We have the json encoded search form data in additionalData.

				$searchFormData = $this->_fixJsSerializedArrayData('conceptScheme', $searchFormData);
				$searchFormData = $this->_fixJsSerializedArrayData('allowedConceptScheme', $searchFormData);
				
				$userForSearch = OpenSKOS_Db_Table_Users::requireById($searchFormData['user']);
				$userSearchOptions = $userForSearch->getSearchOptions($user['id'] != $userForSearch['id']);
				$export->set('searchOptions', Editor_Forms_Search::mergeSearchOptions($searchFormData, $userSearchOptions));
			} break;
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
		$prefLabel = $this->getRequest()->getPost('prefLabel');
		$count = Editor_Models_ApiClient::factory()->getConceptsCountByPrefLabel($prefLabel);
		$this->getHelper('json')->sendJson(array('status' => 'ok', 'result' => array('doExist' => $count > 0)));
	}
	
	/**
	 * Changes the status of all concepts that are in the users selection.
	 * 
	 */
	public function changeSelectionStatusAction()
	{
		$this->_requireAccess('editor.concepts', 'edit', self::RESPONSE_TYPE_JSON);
		
		$status = $this->getRequest()->getPost('status');
		if ( ! empty($status)) {
			$user = $this->getCurrentUser();
			$concepts = $user->getConceptsSelection();
			
			foreach ($concepts as $key => $concept) {
				
				$oldData = $concept->getData();
				
				// It is not allowed to edit concepts of different tenants.
				if ($oldData['tenant'] != $user->tenant) {
					continue;
				}
				
				// The real update data...
				$updateExtraData['status'] = $status;
				
				$updateExtraData['modified_by'] = $user->id;
				$updateExtraData['modified_timestamp'] = date("Y-m-d\TH:i:s\Z");
				
				if ($oldData['status'] != 'approved' && $status == 'approved') {
					$updateExtraData['approved_by'] = $user->id;
					$updateExtraData['approved_timestamp'] = date("Y-m-d\TH:i:s\Z");
				}
				
				// The actual update...
				$doCommit = ($key == (count($concepts) - 1)); // Commit only on the last concept.
						
				$concept = new Editor_Models_Concept($concept);				
				$concept->update(array(), $updateExtraData, $doCommit);
			}
		}
		
		$this->getHelper('json')->sendJson(array('status' => 'ok'));
	}
	
	/**
	 * @return Api_Models_Concept
	 */
	protected function _getConcept()
	{
		$uuid = $this->getRequest()->getParam('uuid');
		if (null === $uuid || empty($uuid)) {
			return null;
		}
		
		$response  = Api_Models_Concepts::factory()->getConcepts('uuid:'.$uuid);
		if (!isset($response['response']['docs']) || (1 !== count($response['response']['docs']))) {			
			throw new Zend_Exception('The requested concept was not found');
		} else {
			return new Editor_Models_Concept(new Api_Models_Concept(array_shift($response['response']['docs'])));
		}
	}
	
	/**
	 * @FIXME There is too much logic data in the view, it should be moved to helpers and loaded before rendering.
	 * A lot of the data should be loaded into the view from the controller.
 	 */
	protected function _generateFooter(Api_Models_Concept &$concept)
	{
		$footerData = array();
		$footerFields = array('created', 'modified', 'approved');
		foreach ($footerFields as $field) {
			if (isset($concept[$field.'_by'])) {
				$user = $this->_getUser($concept[$field.'_by']);
			}
			if (isset($concept[$field.'_timestamp'])) {
				$serverTimeZone = new DateTimeZone(ini_get('date.timezone'));
				$date = new DateTime($concept[$field.'_timestamp']);
				$date->setTimezone($serverTimeZone);
				$date = $date->format('d-m-Y H:i:s');
			} else {
				$date = '';
			}
			
			if ($field == 'created') {
				$footerData[$field]['user'] = isset($user) && (null !== $user) ? $user->name : (isset($concept['dcterms_creator']) && ! empty($concept['dcterms_creator']) ? $concept['dcterms_creator'][0] : 'N/A');
			} else {
				$footerData[$field]['user'] = isset($user) && (null !== $user) ? $user->name : 'N/A';
			}
			$footerData[$field]['date'] = ! empty($date) ? $date : 'N/A';
		} 
		return $footerData;
	}
	
	/**
	 * @return OpenSKOS_Db_Table_Row_User
	 */
	protected function _getUser($id)
	{
		$model = new OpenSKOS_Db_Table_Users();
		$user = $model->find((int)$id)->current();
		return $user;
	}
	
	/**
	 * Check if the user's tenant and the concept's tenant are the same. 
	 * If not - do not allow edit and return to view with error.
	 * 
	 * @param $concept Api_Models_Concept
	 */
	protected function _checkConceptTenantForEdit($concept)
	{
		if (null !== $concept) {
			$conceptTenantData = $concept->toArray(array('tenant'));
			if ($conceptTenantData['tenant'] != $this->getCurrentUser()->tenant) {
				$this->getHelper('FlashMessenger')->setNamespace('error')->addMessage(_('You can not edit concepts of different tenants.'));
				$this->_helper->redirector('view', 'concept', 'editor', array('uuid' => $this->getRequest()->getParam('uuid')));
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
}