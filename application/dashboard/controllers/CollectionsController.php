<?php

class Dashboard_CollectionsController extends OpenSKOS_Controller_Dashboard
{
	public function indexAction()
	{
		$this->view->collections = $this->_tenant->findDependentRowset('OpenSKOS_Db_Table_Collections');
		
		$model = new OpenSKOS_Db_Table_Collections();
	}
	
	public function editAction()
	{
		$collection = $this->_getCollection();
		$this->view->assign('collection', $collection);
		$this->view->assign('jobs', $collection->findDependentRowset('OpenSKOS_Db_Table_Jobs'));
		
		$this->view->assign('max_upload_size', Zend_Controller_Front::getInstance()->getParam('bootstrap')->getOption('max_upload_size'));
	}
	
	public function importAction()
	{
		$collection = $this->_getCollection();
		$form = $collection->getUploadForm();
		$formData = $this->_request->getPost();
		if ($form->isValid($formData)) {
			$upload = new Zend_File_Transfer_Adapter_Http();
			$path = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getOption('upload_path');
			$tenant_path = $path .'/'.$collection->tenant;
			if (!is_dir($tenant_path)) {
				if (!@mkdir($tenant_path)) {
					$this->getHelper('FlashMessenger')->setNamespace('error')->addMessage('Failed to create upload folder');
					$this->_helper->redirector('edit', null, null, array('collection' => $collection->code));
					return;
				}
			}
			try {
				$upload
					->addFilter('Rename', array(
						'target' => $tenant_path . '/' . $_FILES['xml']['name'], 
						'overwrite' => false))
					->receive();
			} catch (Zend_File_Transfer_Exception $e) {
	 			$form->getElement('xml')->setErrors(array($e->getMessage()));
				return $this->_forward('edit');
	 		} catch (Zend_Filter_Exception $e) {
	 			$form->getElement('xml')->setErrors(array('A file with that name is already scheduled for import'));
				return $this->_forward('edit');
	 		}
	 		$model = new OpenSKOS_Db_Table_Jobs();
	 		$fileinfo = $upload->getFileInfo('xml');
	 		$job = $model->fetchNew()->setFromArray(array(
	 			'collection' => $collection->id,
	 			'user' => Zend_Auth::getInstance()->getIdentity()->id,
	 			'task' => 'import',
	 			'parameters' => serialize($fileinfo['xml']),
	 			'created' => new Zend_Db_Expr('NOW()')
	 		))->save();
		} else {
			return $this->_forward('edit');
		}
		$this->getHelper('FlashMessenger')->addMessage('An import job is scheduled');
		$this->_helper->redirector('edit', null, null, array('collection' => $collection->code));
	}

	public function saveAction()
	{
		if (!$this->getRequest()->isPost()) {
			$this->getHelper('FlashMessenger')->setNamespace('error')->addMessage('No POST data recieved');
			$this->_helper->redirector('index');
		}
		$collection = $this->_getCollection();
		
		if (null!==$this->getRequest()->getParam('delete')) {
			if (!$collection->id) {
				$this->getHelper('FlashMessenger')->setNamespace('error')->addMessage('You can not delete an empty collection.');
				$this->_helper->redirector('index');
			}
			$collection->delete();
			$this->getHelper('FlashMessenger')->addMessage('The collection has been deleted, it might take a while before changes are committed to our system.');
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
				$this->getHelper('FlashMessenger')->setNamespace('error')->addMessage('Collection `'.$code.'` not found');
				$this->_helper->redirector('index');
			}
		}
		return $collection;
	}
	
}