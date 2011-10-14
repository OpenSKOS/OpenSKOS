<?php

class Dashboard_InstitutionController extends OpenSKOS_Controller_Dashboard
{
	public function indexAction()
	{
		$tenant = $this->_getTenant();
		$this->view->assign('tenant', $tenant);
	}
	
	public function saveAction()
	{
		if (!$this->getRequest()->isPost()) {
			$this->getHelper('FlashMessenger')->setNamespace('error')->addMessage('No POST data recieved');
			$this->_helper->redirector('index');
		}
		$tenant = $this->_getTenant();
		$form = $tenant->getForm();
		if (!$form->isValid($this->getRequest()->getParams())) {
			return $this->_forward('index');
		} else {
			$tenant->setFromArray($form->getValues())->save();
			$this->getHelper('FlashMessenger')->addMessage('Data saved');
			$this->_helper->redirector('index');
		}
	}
	
    /**
     * @return OpenSKOS_Db_Table_Row_Tenant
     */
	protected function _getTenant()
	{
		$tenant = OpenSKOS_Db_Table_Tenants::fromIdentity();
		if (null === $tenant) {
			throw new Zend_Controller_Action_Exception('Tenant not found', 404);
		}
		$tenant->getForm()->setAction($this->getFrontController()->getRouter()->assemble(array('action'=>'save')));
		return $tenant;
	}

}

