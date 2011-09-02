<?php


class Api_SolrSchemaController extends OpenSKOS_Rest_Controller {

	public function init()
	{
		parent::init();
		$this->_helper->contextSwitch()
			->initContext('xml');
		Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer')->setNoRender(true);
		$this->getHelper('layout')->disableLayout();
	}
	
	public function indexAction() 
	{
		echo Zend_Registry::get('OpenSKOS_Solr')->getSchema(false);
	}

	public function getAction() {
		$this->_501('GET');
	}

	public function postAction() {
		$this->_501('POST');
	}

	public function putAction() {
		$this->_501('PUT');
	}

	public function deleteAction() {
		$this->_501('DELETE');
	}

}

