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
 * @author     Mark Lindeman
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 */

class Editor_CollectionsController extends OpenSKOS_Controller_Editor
{
	public function indexAction()
	{
		$this->_requireAccess('editor.collections', 'index');
		
		$this->view->collections = $this->_tenant->findDependentRowset('OpenSKOS_Db_Table_Collections');
		
		$model = new OpenSKOS_Db_Table_Collections();
	}
	
	public function editAction()
	{
		$this->_requireAccess('editor.collections', 'manage');
		
		$collection = $this->_getCollection();
		
		if (! OpenSKOS_Db_Table_Users::fromIdentity()->isAllowed('editor.delete-all-concepts-in-collection', null)) {
			$collection->getUploadForm()->removeElement('delete-before-import');			
		}
		
		$this->view->assign('collection', $collection);
		$this->view->assign('jobs', $collection->getJobs());
		$this->view->assign('harvestjobs', $collection->getJobs(OpenSKOS_Db_Table_Row_Job::JOB_TASK_HARVEST));
		$this->view->assign('max_upload_size', Zend_Controller_Front::getInstance()->getParam('bootstrap')->getOption('max_upload_size'));
	}
	
	public function harvestAction()
	{
		$this->_requireAccess('editor.collections', 'manage');
		
		$collection = $this->_getCollection();
		if (!$collection->OAI_baseURL) {
			$this->getHelper('FlashMessenger')->setNamespace('error')->addMessage(_('This collection does not appear to have a OAI Server as source.'));
			$this->_helper->redirector('edit', null, null, array('collection' => $collection->code));
			return;
		}
		$form = $collection->getOaiJobForm();
		$formData = $this->_request->getPost();
		if ($form->isValid($formData)) {
	 		$parameters = $form->getValues();
	 		$parameters['url'] = $collection->OAI_baseURL;
	 		if (isset($parameters['from']) && $parameters['from']) {
	 			$parameters['from'] = strtotime($parameters['from']);
	 		}
	 		if (isset($parameters['until']) && $parameters['until']) {
	 			$parameters['until'] = strtotime($parameters['until']);
	 		}
	 		
	 		if (isset($parameters['set'])) {
	 			foreach ($form->getElement('set')->getMultiOptions() as $setSpec => $setName) {
	 				if ($setSpec == $parameters['set']) {
	 					$parameters['setName'] = $setName;
	 					break;
	 				}
	 			}
	 		}
	 		
	 		$parameters['deletebeforeimport'] = (int)$parameters['deletebeforeimport'] == 1;
	 		$model = new OpenSKOS_Db_Table_Jobs();
	 		$job = $model->fetchNew()->setFromArray(array(
	 			'collection' => $collection->id,
	 			'user' => Zend_Auth::getInstance()->getIdentity()->id,
	 			'task' => OpenSKOS_Db_Table_Row_Job::JOB_TASK_HARVEST,
	 			'parameters' => serialize($parameters),
	 			'created' => new Zend_Db_Expr('NOW()')
	 		))->save();
		} else {
			return $this->_forward('edit');
		}
		$this->getHelper('FlashMessenger')->addMessage(_('An OAI Harvest job is scheduled'));
		$this->_helper->redirector('edit', null, null, array('collection' => $collection->code));
	}
	
	public function importAction()
	{
		$this->_requireAccess('editor.collections', 'manage');
		
		$collection = $this->_getCollection();
		if ($collection->OAI_baseURL) {
			$this->getHelper('FlashMessenger')->setNamespace('error')->addMessage(_('Since this collection has an OAI Server as source, you can not upload files for import.'));
			$this->_helper->redirector('edit', null, null, array('collection' => $collection->code));
			return;
		}
		$form = $collection->getUploadForm();
		$formData = $this->_request->getPost();
		if ($form->isValid($formData)) {
			$upload = new Zend_File_Transfer_Adapter_Http();
			$path = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getOption('upload_path');
			$tenant_path = $path .'/'.$collection->tenant;
			if (!is_dir($tenant_path)) {
				if (!@mkdir($tenant_path, 0777, true)) {
					$this->getHelper('FlashMessenger')->setNamespace('error')->addMessage(_('Failed to create upload folder'));
					$this->_helper->redirector('edit', null, null, array('collection' => $collection->code));
					return;
				}
			}
			try {
				$fileName = uniqid() . '_' . $_FILES['xml']['name'];
				$upload
					->addFilter('Rename', array(
						'target' => $tenant_path . '/' . $fileName, 
						'overwrite' => false))
					->receive();
			} catch (Zend_File_Transfer_Exception $e) {
	 			$form->getElement('xml')->setErrors(array($e->getMessage()));
				return $this->_forward('edit');
	 		} catch (Zend_Filter_Exception $e) {
	 			$form->getElement('xml')->setErrors(array(_('A file with that name is already scheduled for import. Please delete the job if you want to import it again.')));
				return $this->_forward('edit');
	 		}
	 		$model = new OpenSKOS_Db_Table_Jobs();
	 		$fileinfo = $upload->getFileInfo('xml');
	 		$parameters = array(
	 			'name' => $fileName,
	 			'type' => $fileinfo['xml']['type'],
	 			'size' => $fileinfo['xml']['size'],
	 			'destination' => $fileinfo['xml']['destination'],
	 			'deletebeforeimport' => (int)$formData['deletebeforeimport'] == 1,
 				'status' => $formData['status'],
 				'ignoreIncomingStatus' => (int)$formData['ignoreIncomingStatus'] == 1,
 				'lang' => $formData['lang'],
 				'toBeChecked' => (int)$formData['toBeChecked'] == 1,
	 			'purge' => (int)$formData['purge'] == 1,
	 			'onlyNewConcepts' => (int)$formData['onlyNewConcepts'] == 1
	 		);
	 		$job = $model->fetchNew()->setFromArray(array(
	 			'collection' => $collection->id,
	 			'user' => Zend_Auth::getInstance()->getIdentity()->id,
	 			'task' => OpenSKOS_Db_Table_Row_Job::JOB_TASK_IMPORT,
	 			'parameters' => serialize($parameters),
	 			'created' => new Zend_Db_Expr('NOW()')
	 		))->save();
		} else {
			return $this->_forward('edit');
		}
		$this->getHelper('FlashMessenger')->addMessage(_('An import job is scheduled'));
		$this->_helper->redirector('edit', null, null, array('collection' => $collection->code));
	}

	public function saveAction()
	{
		$this->_requireAccess('editor.collections', 'manage');
		
		if (!$this->getRequest()->isPost()) {
			$this->getHelper('FlashMessenger')->setNamespace('error')->addMessage(_('No POST data recieved'));
			$this->_helper->redirector('index');
		}
		$collection = $this->_getCollection();
		
		if (null!==$this->getRequest()->getParam('delete')) {
			if (!$collection->id) {
				$this->getHelper('FlashMessenger')->setNamespace('error')->addMessage(_('You can not delete an empty collection.'));
				$this->_helper->redirector('index');
			}
			$collection->delete();
			$this->getHelper('FlashMessenger')->addMessage(_('The collection has been deleted, it might take a while before changes are committed to our system.'));
			$this->_helper->redirector('index');
		}
		
		$form = $collection->getForm();
		if (!$form->isValid($this->getRequest()->getParams())) {
			return $this->_forward('edit');
		} else {
			$collection
				->setFromArray($form->getValues())
				->setFromArray(array('tenant' => $this->_tenant->code));
			try {
				$collection->save();
			} catch (Zend_Db_Statement_Exception $e) {
				$this->getHelper('FlashMessenger')->setNamespace('error')->addMessage($e->getMessage());
				return $this->_forward('edit');
			}
			$this->getHelper('FlashMessenger')->addMessage('Data saved');
			$this->_helper->redirector('index');
		}
	}
	
	public function getConceptsBaseUrlAction()
	{
		$model = new OpenSKOS_Db_Table_Collections();
		$collection = $model->find($this->getRequest()->getParam('id'))->current();
		
		$conceptsBaseUrl = $collection->getConceptsBaseUri();
		
		$this->getHelper('json')->sendJson(array('status' => 'ok', 'result' => $conceptsBaseUrl));
	}
	
	/**
	 * @return OpenSKOS_Db_Table_Row_Collection
	 */
	protected function _getCollection()
	{
		$model = new OpenSKOS_Db_Table_Collections();
		if (null === ($code = $this->getRequest()->getParam('collection'))) {
			//create a new collection:
			$collection = $model->createRow(array('tenant' => $this->_tenant->code));
		} else {
			$collection = $model->findByCode($code, $this->_tenant->code);
			if (null === $collection) {
				$this->getHelper('FlashMessenger')->setNamespace('error')->addMessage(sprintf(_('Collection `%s` not found', $code)));
				$this->_helper->redirector('index');
			}
		}
		return $collection;
	}
	
}