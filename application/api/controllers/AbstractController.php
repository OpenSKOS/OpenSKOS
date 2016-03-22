<?php

abstract class AbstractController extends OpenSKOS_Rest_Controller

{
    protected $resourceClass;
    protected $fullNameResourceClass;
    protected $indexProperty;
    
    public function init()
    {
        $this->getHelper('layout')->disableLayout();
        $this->getHelper('viewRenderer')->setNoRender(true);
        parent::init();
    }
    
      public function indexAction()
    {
        if ('json' !== $this->_helper->contextSwitch()->getCurrentContext()) {
            $this->_501('This action, which lists the uris of all ' . $this -> resourceClass . ' is currently  implemented only for json format output.');
        };
        $resourceManager = $this -> getResourceManager();
        $result = $resourceManager ->fetchObjectsWithProperty($this -> indexProperty);
        $this->_helper->contextSwitch()->setAutoJsonSerialization(false);
        $this->getResponse()->setBody(json_encode($result, JSON_UNESCAPED_SLASHES));
    }
   
     public function getAction()
    {
       $this->_helper->viewRenderer->setNoRender(true);
       $api = $this->getDI()->make($this->fullNameResourceClass);
        
        // Exception for html use ZF 1 easier with linking in the view
        if ('html' === $this ->getParam('format')) {
            //$this->view->concept = $apiConcept->getConcept($id);
            //return $this->renderScript('concept/get.phtml');
            throw new Exception('HTML format is not implemented yet', 404);
        }
        
        $request = $this->getPsrRequest();
        $response = $api->findResourceById($request);
        $this->emitResponse($response);
    }
    
     public function postAction()
    {
        $request = $this->getPsrRequest();
        $api = $this->getDI()->make($this->fullNameResourceClass);
        $response = $api->create($request);
        $this->emitResponse($response);
    }
    
     public  function putAction()
    {
        $request = $this->getPsrRequest();
        $api = $this->getDI()->make($this->fullNameResourceClass);
        $response = $api->update($request);
        $this->emitResponse($response);
    }
    
     public  function deleteAction()
    {
        $request = $this->getPsrRequest();
        $api = $this->getDI()->make($this->fullNameResourceClass);
        $response = $api->deleteResourceObject($request);
        $this->emitResponse($response);
    }
}