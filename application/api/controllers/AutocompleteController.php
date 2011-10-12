<?php

class Api_AutocompleteController extends OpenSKOS_Rest_Controller {

	/**
	 * 
	 * @var Concepts
	 */
	protected $model;
	
	public function init()
	{
		$this->model = Api_Models_Concepts::factory()->setQueryParams(
			$this->getRequest()->getParams()
		);
		parent::init();
		$this->_helper->contextSwitch()
			->initContext($this->getRequest()->getParam('format', 'json'));
		$this->view->setEncoding('UTF-8');
	}
	
	public function indexAction() {
		$this->_501('GET');
	}

	public function getAction() {
		$this->_helper->contextSwitch()->setAutoJsonSerialization(false);
		echo json_encode($this->model->autocomplete($this->getRequest()->getParam('id')));
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

