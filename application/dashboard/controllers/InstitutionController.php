<?php

class Dashboard_InstitutionController extends OpenSKOS_Controller_Dashboard
{
	public function indexAction()
	{
		$this->view->assign('tenant', $this->_tenant);
	}
	
	public function saveAction()
	{
		if (!$this->getRequest()->isPost()) {
			$this->getHelper('FlashMessenger')->setNamespace('error')->addMessage('No POST data recieved');
			$this->_helper->redirector('index');
		}
		$form = $this->_tenant->getForm();
		if (!$form->isValid($this->getRequest()->getParams())) {
			return $this->_forward('index');
		} else {
			$this->_tenant->setFromArray($form->getValues())->save();
			$this->getHelper('FlashMessenger')->addMessage('Data saved');
			$this->_helper->redirector('index');
		}
	}
	
}

