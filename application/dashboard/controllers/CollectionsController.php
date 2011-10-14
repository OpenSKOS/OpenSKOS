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
		$this->view->assign('collection', $this->_getCollection());
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