<?php

class OaiPmh_IndexController extends OpenSKOS_Rest_Controller
{
	public function init()
	{
		$this->getHelper('layout')->disableLayout();
		$this->getResponse()->setHeader('Content-Type' , 'text/xml; charset=utf8');
		
	}
	
	public function indexAction() 
	{
		require_once APPLICATION_PATH . '/' . $this->getRequest()->getModuleName() .'/models/OaiPmh.php';
		$this->view->responseDate = date(DateTime::ISO8601); 
			
		$oai = new OaiPmh($this->getRequest()->getParams(), $this->view);
		$oai->setBaseUrl('http:'.($_SERVER['SERVER_PORT']==443?'s':'') . '//'
			.$_SERVER['HTTP_HOST']
			. $this->getFrontController()->getRouter()->assemble(array())
		);
		$this->view->oai = $oai;
		
	}
	
	public function getAction() 
	{
		$this->_501('GET');
	}
	
	public function postAction() {
		$this->_501('POST');
	}

	public function putAction() {
		$this->_501('POST');
	}

	public function deleteAction() {
		$this->_501('DELETE');
	}
}