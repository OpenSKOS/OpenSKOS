<?php

class Api_FindConceptsController extends OpenSKOS_Rest_Controller {

	/**
	 * 
	 * @var Api_Models_Concepts
	 */
	protected $model;
	
	public function init()
	{
		parent::init();
		$this->model = Api_Models_Concepts::factory()->setQueryParams(
			$this->getRequest()->getParams()
		);
		$this->_helper->contextSwitch()
			->initContext($this->getRequest()->getParam('format', 'json'));
	}
	
	public function indexAction() {
		if (null === ($q = $this->getRequest()->getParam('q'))) {
			$this->getResponse()
				->setHeader('X-Error-Msg', 'Missing required parameter `q`');
			throw new Zend_Controller_Exception('Missing required parameter `q`', 400);
		}
		$concepts = $this->model->getConcepts($q);
		if ($this->getRequest()->getParam('format') === 'json') {
			foreach ($concepts as $key => $val) {
				$this->view->$key = $val;
			}
		} else {
			$this->view->response = $concepts;
		}
	}

	public function getAction() {
		
		$id = $this->getRequest()->getParam('id');
		$concept = $this->model->getConcept($id);
		if ($this->_helper->contextSwitch()->getCurrentContext()==='json') {
			if (null !== $concept) {
				foreach ($concept as $key => $var) {
					$this->view->$key = $var;
				}
			}
		} else {
			$this->view->concept = $concept;
		}
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

