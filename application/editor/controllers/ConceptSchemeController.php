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

class Editor_ConceptSchemeController extends OpenSKOS_Controller_Editor
{
	public function init()
	{
		parent::init();
		$this->_checkTenantFolders();
	}
	
	/**
	 * Lists all concept schemes.
	 */
	public function indexAction()
	{
		$this->_requireAccess('editor.concept-schemes', 'index', self::RESPONSE_TYPE_HTML);
		
		// Clears the schemes cache when we start managing them.
		OpenSKOS_Cache::getCache()->remove(Editor_Models_ApiClient::CONCEPT_SCHEMES_CACHE_KEY);
		
		$this->view->uploadedIcons = $this->_getUploadedIcons();		
		$this->view->conceptSchemes = Editor_Models_ApiClient::factory()->getConceptSchemes();
		
		$this->view->conceptSchemesWithDeleteJobs = $this->_getConceptSchemesWithDeleteJob();
		
		$user = OpenSKOS_Db_Table_Users::fromIdentity();
		$modelCollections = new OpenSKOS_Db_Table_Collections();
		$this->view->collectionsMap = $modelCollections->getIdToTitleMap($user->tenant);
	}
	
	/**
	 * Creates new concept scheme.
	 * 
	 */
	public function createAction()
	{	
		$this->_helper->_layout->setLayout('editor_central_content');
		
		$this->_requireAccess('editor.concept-schemes', 'create', self::RESPONSE_TYPE_PARTIAL_HTML);
	
		$conceptScheme = Editor_Models_ConceptScheme::factory();
	
		$form = Editor_Forms_ConceptScheme::getInstance(true);
		
		if ($this->getRequest()->isPost()) {			
			$this->view->errors = $form->getErrors();
			
			$formData = $this->getRequest()->getPost();
			$uriCode = $formData['uriCode'];
			$uriBase = $formData['uriBase'];
			$extraData = $conceptScheme->transformFormData($formData);
			$conceptScheme->setConceptData($formData);
			$formData = $conceptScheme->toForm($extraData, $uriCode, $uriBase);
			
			$form->reset();
			$form->populate($formData);
		}
		
		$this->view->form = $form->setAction($this->getFrontController()->getRouter()->assemble(array('controller' => 'concept-scheme', 'action' => 'save')));
	}
	
	/**
	 * Saves new or existing concept scheme.
	 * 
	 */
	public function saveAction()
	{
		$this->_helper->_layout->setLayout('editor_central_content');
		
		$this->_requireAccess('editor.concept-schemes', 'create', self::RESPONSE_TYPE_PARTIAL_HTML);
		
		$form = Editor_Forms_ConceptScheme::getInstance();
		$formData = $this->getRequest()->getParams();
		
		if ( ! $this->getRequest()->isPost()) {
			$this->getHelper('FlashMessenger')->setNamespace('error')->addMessage(_('No POST data recieved'));
			$this->_helper->redirector('edit');
		}
		
		if ( ! $form->isValid($formData)) {
			return $this->_forward('create');
		} else {
			
			$form->populate($formData);
			
			$conceptScheme = $this->_getConceptScheme();
			
			if (null === $conceptScheme) {
				$this->_requireAccess('editor.concept-schemes', 'create', self::RESPONSE_TYPE_PARTIAL_HTML);
				$conceptScheme = new Editor_Models_ConceptScheme(new Api_Models_Concept());
			} else {
				$this->_requireAccess('editor.concept-schemes', 'edit', self::RESPONSE_TYPE_PARTIAL_HTML);
			}
			
			$oldData = $conceptScheme->getData();
			$extraData = $conceptScheme->transformFormData($formData);
			$conceptScheme->setConceptData($formData, $extraData);
			
			try {
				$user = OpenSKOS_Db_Table_Users::fromIdentity();
			
				$extraData = array_merge($extraData, array(						
						'tenant' => $user->tenant,
						'modified_by' => (int)$user->id,
						'modified_timestamp' =>  date("Y-m-d\TH:i:s\Z"))
				);
				
				if ( ! isset($extraData['uuid']) || empty($extraData['uuid'])) {
					$extraData['uuid'] = $conceptScheme['uuid'];
					$extraData['created_by'] = $extraData['modified_by'];
					$extraData['created_timestamp'] = $extraData['modified_timestamp'];
				} else {
					$extraData['created_by'] = $oldData['created_by'];
					$extraData['created_timestamp'] = $oldData['created_timestamp'];
				}
				
				$conceptScheme->save($extraData);
				
				// Clears the schemes cache after a new scheme is added.
				OpenSKOS_Cache::getCache()->remove(Editor_Models_ApiClient::CONCEPT_SCHEMES_CACHE_KEY);
				
			} catch (Zend_Exception $e) {
				$this->getHelper('FlashMessenger')->setNamespace('error')->addMessage($e->getMessage());
				return $this->_forward('edit');
			}
		}
	}
	
	/**
	 * Fully remove the concept scheme.
	 * 
	 */
	public function deleteAction()
	{
		$this->_requireAccess('editor.concept-schemes', 'delete', self::RESPONSE_TYPE_HTML);
		
		$user = $this->getCurrentUser();
		$conceptScheme = $this->_getConceptScheme();
		
		$getConceptSchemesWithDeleteJob = $this->_getConceptSchemesWithDeleteJob();
		if (! isset($getConceptSchemesWithDeleteJob[$conceptScheme['uuid']])) {
			$model = new OpenSKOS_Db_Table_Jobs();
			$job = $model->fetchNew()->setFromArray(array(
					'collection' => $conceptScheme['collection'],
					'user' => $user->id,
					'task' => OpenSKOS_Db_Table_Row_Job::JOB_TASK_DELETE_CONCEPT_SCHEME,
					'parameters' => serialize(array('uuid' => $conceptScheme['uuid'])),
					'created' => new Zend_Db_Expr('NOW()')
			))->save();
			
			$this->getHelper('FlashMessenger')->addMessage(_('A job for deleting the concept scheme was added.'));
		} else {			
			$this->getHelper('FlashMessenger')->addMessage(_('A job for deleting the concept scheme already exists.'));
		}
		$this->_helper->redirector('index');
	}
	
	/**
	 * List all uploaded icons.
	 * 
	 */
	public function showIconsAction()
	{
		$this->_requireAccess('editor.concept-schemes', 'manage-icons', self::RESPONSE_TYPE_HTML);
		
		$this->view->uploadedIcons = $this->_getUploadedIcons();
		$this->view->uploadIconForm = Editor_Forms_UploadIcon::getInstance();
	}
	
	/**
	 * Uploads new icon.
	 * 
	 */
	public function uploadIconAction()
	{
		$this->_requireAccess('editor.concept-schemes', 'manage-icons', self::RESPONSE_TYPE_HTML);
		
		$uploadIconForm = Editor_Forms_UploadIcon::getInstance();
		if ( ! $uploadIconForm->isValid(array())) {
			return $this->_forward('show-icons');
		}
	
		if ( ! $uploadIconForm->icon->receive()) {
			return $this->_forward('show-icons');
		}
	
		// Load options needed for converting the image to icon.
		$editorOptions = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getOption('editor');
	
		if (isset($editorOptions['schemeIcons']) && isset($editorOptions['schemeIcons']['width'])) {
			$convertToWidth = $editorOptions['schemeIcons']['width'];
		} else {
			$convertToWidth = 16;
		}
	
		if (isset($editorOptions['schemeIcons']) && isset($editorOptions['schemeIcons']['height'])) {
			$convertToHeight = $editorOptions['schemeIcons']['height'];
		} else {
			$convertToHeight = 16;
		}
	
		if (isset($editorOptions['schemeIcons']) && isset($editorOptions['schemeIcons']['extension'])) {
			$convertToExtension = $editorOptions['schemeIcons']['extension'];
		} else {
			$convertToExtension = 'png';
		}
	
		$uploadedIconPath = $uploadIconForm->icon->getFileName();
	
		OpenSKOS_ImageConverter::convertTo($uploadedIconPath, $convertToWidth, $convertToHeight, $convertToExtension);
	
		// If the uploaded extension differes from the extension in which the image is converted - we remove the upload image.
		if (strcasecmp(substr($uploadedIconPath, strrpos($uploadedIconPath, '.') + 1), $convertToExtension)) {
			unlink($uploadedIconPath);
		}
	
		return $this->_forward('show-icons');
	}
	
	/**
	 * Assign icon to a scheme.
	 * 
	 */
	public function assignIconAction()
	{
		$this->_requireAccess('editor.concept-schemes', 'manage-icons', self::RESPONSE_TYPE_JSON);
		
		$schemeUuid = $this->getRequest()->getParam('schemeUuid');
		$iconToAssign = $this->getRequest()->getParam('iconFile');
	
		$editorOptions = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getOption('editor');
		if (isset($editorOptions['schemeIcons']) && isset($editorOptions['schemeIcons']['uploadPath'])) {
			$iconsUploadPath = APPLICATION_PATH . $editorOptions['schemeIcons']['uploadPath'] . '/' . $this->_tenant->code;
		} else {
			$iconsUploadPath = APPLICATION_PATH . Editor_Forms_UploadIcon::DEFAULT_UPLOAD_PATH . '/' . $this->_tenant->code;
		}
	
		if (isset($editorOptions['schemeIcons']) && isset($editorOptions['schemeIcons']['assignPath'])) {
			$iconsAssignPath = APPLICATION_PATH . $editorOptions['schemeIcons']['assignPath'] . '/' . $this->_tenant->code;
		} else {
			$iconsAssignPath = APPLICATION_PATH . Editor_Forms_UploadIcon::DEFAULT_ASSIGN_PATH . '/' . $this->_tenant->code;
		}
	
		if (isset($editorOptions['schemeIcons']) && isset($editorOptions['schemeIcons']['extension'])) {
			$iconsExtension = $editorOptions['schemeIcons']['extension'];
		} else {
			$iconsExtension = 'png';
		}
	
		copy($iconsUploadPath . '/' . $iconToAssign, $iconsAssignPath . '/' . $schemeUuid . '.' . $iconsExtension);
	
		// Clears the schemes cache after a scheme icon is changed.
		OpenSKOS_Cache::getCache()->remove(Editor_Models_ApiClient::CONCEPT_SCHEMES_CACHE_KEY);
	
		$this->getHelper('json')->sendJson(array('status' => 'ok', 'result' => array('newIconPath' => Editor_Models_ConceptScheme::buildIconPath($schemeUuid))));
	}
	
	/**
	 * Delete an icon.
	 * 
	 */
	public function deleteIconAction()
	{
		$this->_requireAccess('editor.concept-schemes', 'manage-icons', self::RESPONSE_TYPE_JSON);
		
		$iconToDelete = $this->getRequest()->getParam('iconFile');
	
		$editorOptions = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getOption('editor');
		if (isset($editorOptions['schemeIcons']) && isset($editorOptions['schemeIcons']['uploadPath'])) {
			$iconsUploadPath = APPLICATION_PATH . $editorOptions['schemeIcons']['uploadPath'] . '/' . $this->_tenant->code;
		} else {
			$iconsUploadPath = APPLICATION_PATH . Editor_Forms_UploadIcon::DEFAULT_UPLOAD_PATH . '/' . $this->_tenant->code;
		}
	
		unlink($iconsUploadPath . '/' . $iconToDelete);
	
		$this->getHelper('json')->sendJson(array('status' => 'ok'));
	}
	
	public function getConceptsBaseUrlAction()
	{
		$conceptScheme = $this->_getConceptScheme();
		
		$conceptsBaseUrl = $conceptScheme->getCollection()->getConceptsBaseUri();
	
		$this->getHelper('json')->sendJson(array('status' => 'ok', 'result' => $conceptsBaseUrl));
	}
	
	/**
	 * Get an array of the uploaded icons paths.
	 * 
	 */
	protected function _getUploadedIcons()
	{
		$editorOptions = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getOption('editor');
	
		if (isset($editorOptions['schemeIcons']) && isset($editorOptions['schemeIcons']['uploadPath'])) {
			$iconsUploadPath = APPLICATION_PATH . $editorOptions['schemeIcons']['uploadPath'] . '/' . $this->_tenant->code;
		} else {
			$iconsUploadPath = APPLICATION_PATH . Editor_Forms_UploadIcon::DEFAULT_UPLOAD_PATH . '/' . $this->_tenant->code;
		}
	
		if (isset($editorOptions['schemeIcons']) && isset($editorOptions['schemeIcons']['uploadHttpPath'])) {
			$iconsUploadHttpPath = $editorOptions['schemeIcons']['uploadHttpPath'] . '/' . $this->_tenant->code;
		} else {
			$iconsUploadHttpPath = Editor_Forms_UploadIcon::DEFAULT_UPLOAD_HTTP_PATH . '/' . $this->_tenant->code;
		}
	
		$rawIcons = scandir($iconsUploadPath);
	
		$icons = array();
		foreach ($rawIcons as $icon) {
			if ($icon != '.' && $icon != '..') {
				$icons[] = array('httpPath' => $iconsUploadHttpPath . '/' . $icon, 'iconFile' => $icon);
			}
		}
	
		return $icons;
	}
	
	/**
	 * Check if the tenant folders are created and create them.
	 * 
	 */
	protected function _checkTenantFolders()
	{
		$editorOptions = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getOption('editor');
	
		if (isset($editorOptions['schemeIcons']) && isset($editorOptions['schemeIcons']['uploadPath'])) {
			$iconsUploadPath = APPLICATION_PATH . $editorOptions['schemeIcons']['uploadPath'] . '/' . $this->_tenant->code;
		} else {
			$iconsUploadPath = APPLICATION_PATH . Editor_Forms_UploadIcon::DEFAULT_UPLOAD_PATH . '/' . $this->_tenant->code;
		}
	
		if (isset($editorOptions['schemeIcons']) && isset($editorOptions['schemeIcons']['assignPath'])) {
			$iconsAssignPath = APPLICATION_PATH . $editorOptions['schemeIcons']['assignPath'] . '/' . $this->_tenant->code;
		} else {
			$iconsAssignPath = APPLICATION_PATH . Editor_Forms_UploadIcon::DEFAULT_ASSIGN_PATH . '/' . $this->_tenant->code;
		}
	
		if ( ! is_dir($iconsUploadPath)) {
			mkdir($iconsUploadPath, '0777', true);
		}
	
		if ( ! is_dir($iconsAssignPath)) {
			mkdir($iconsAssignPath, '0777', true);
		}
	}
	
	/**
	 * @return Editor_Models_ConceptScheme
	 */
	protected function _getConceptScheme()
	{
		$uuid = $this->getRequest()->getParam('uuid');
		if (null === $uuid || empty($uuid)) {
			return null;
		}
	
		$response  = Api_Models_Concepts::factory()->getConcepts('uuid:'.$uuid);
		if (!isset($response['response']['docs']) || (1 !== count($response['response']['docs']))) {
			throw new Zend_Exception('The requested concept was not found');
		}
			
		return new Editor_Models_ConceptScheme(new Api_Models_Concept(array_shift($response['response']['docs'])));
	}
	
	/**
	 * Gets an array map for all concept schemes which has a delet job started (and not completed yet) for them.
	 * @return array array(conceptSchemeUuid => deleteJobUuid)
	 */
	protected function _getConceptSchemesWithDeleteJob()
	{
		$model = new OpenSKOS_Db_Table_Jobs();
		$conceptDeleteJobs = $model->fetchAll(
			$model->select()
				->where('task = ?', OpenSKOS_Db_Table_Row_Job::JOB_TASK_DELETE_CONCEPT_SCHEME)
				->where('status IS NULL')
				->where('finished IS NULL')
		);
		
		$conceptSchemesDeleteJobsMap = array();
		foreach ($conceptDeleteJobs as $conceptDeleteJob) {
			$params = $conceptDeleteJob->getParams();
			if (! isset($conceptSchemesDeleteJobsMap[$params['uuid']])) {
				$conceptSchemesDeleteJobsMap[$params['uuid']] = $conceptDeleteJob->id;
			}
		}
		
		return $conceptSchemesDeleteJobsMap;
	}
	
}