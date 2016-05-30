<?php

abstract class AbstractController extends OpenSKOS_Rest_Controller

{
    protected $fullNameResourceClass;
    protected $viewpath;
    
    
    public function init()
    {
        $this->getHelper('layout')->disableLayout();
        $this->getHelper('viewRenderer')->setNoRender(true);
        parent::init();
    }
    
    public function indexAction() {
        $format = $this->getRequestedFormat();
        if ('json' !== $format) {
            throw new Exception('Resource listing is implemented only in format=json', 404);
        }
        $request = $this->getPsrRequest();
        $api = $this->getDI()->make($this->fullNameResourceClass);
        $result = $api->fetchUriName($request);
        $this->_helper->contextSwitch()->setAutoJsonSerialization(false);
        $this->getResponse()->setBody(json_encode($result, JSON_UNESCAPED_SLASHES));
    }

    public function getAction() {
        $request = $this->getPsrRequest();
        $api = $this->getDI()->make($this->fullNameResourceClass);
        $id = $this->getRequest()->getParam('id');
        if (null === $id) {
            throw new Zend_Controller_Exception('No id provided', 400);
        }
        $context = $this->_helper->contextSwitch()->getCurrentContext();
        if ('html' === $context) {
            $this->view->concept = $api->findResourceById($id);
            return $this->renderScript('/get.phtml');
        } else {
            $response = $api->findResourceByIdResponse($request, $id);
            $this->emitResponse($response);
        }
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