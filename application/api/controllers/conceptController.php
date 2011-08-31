<?php

class Api_ConceptController extends OpenSKOS_Rest_Controller {

	/**
	 * 
	 * @var Api_Models_Concepts
	 */
	protected $model;
	
	public function init()
	{
		$this->model = Api_Models_Concepts::factory()->setQueryParams(
			$this->getRequest()->getParams()
		);
		parent::init();
	}
	
	public function indexAction() {
		$this->_501('GET');
	}

	public function getAction() {
		$id = $this->getRequest()->getParam('id');
		if (null === ($concept = $this->model->getConcept($id))) {
			throw new Zend_Controller_Action_Exception('Concept not found', 404);
		}
		$this->view->concept = $concept;
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

