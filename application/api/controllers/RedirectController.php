<?php

class Api_RedirectController extends Zend_Controller_Action
{
	public function indexAction()
	{
		$this->model = Api_Models_Concepts::factory()->setQueryParams(
			$this->getRequest()->getParams()
		);
		$this->_helper->contextSwitch()
			->initContext($this->getRequest()->getParam('format', 'rdf'));
		
		$this->getHelper('layout')->disableLayout();
		
		$id = $this->getRequest()->getParam('id');
		if (null === $id) {
			throw new Zend_Controller_Exception('No id `'.$id.'` provided', 400);
		}
		
		$concept = $this->model->getConcept($id);
		if (null === $concept) {
			throw new Zend_Controller_Exception('Concept `'.$id.'` not found', 404);
		}
		
		$router = Zend_Controller_Front::getInstance()->getRouter();
		$uri = $router->assemble(array(
			'id' => $concept['uuid'],
			'module' => 'api',
			'controller' => 'concept',
		), 'rest', true);
		switch ($this->getRequest()->getParam('format')) {
			case 'json':
			case 'html':
				$uri .= '.' . $this->getRequest()->getParam('format');
				break;
		}
		$this->_helper->redirector->setGotoUrl($uri);
		$this->_helper->redirector->redirectAndExit();
	}

	public function getAction()
	{
		$this->indexAction();
	}
}