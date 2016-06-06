<?php


use EasyRdf\RdfNamespace;
use OpenSkos2\Namespaces\vCard;
use OpenSkos2\Api\Response\Detail\JsonpResponse;


abstract class AbstractController extends OpenSKOS_Rest_Controller

{
    protected $fullNameResourceClass;
    protected $viewpath;
    public function init()
    {
        parent::init();
        $this->getHelper('viewRenderer')->setNoRender(true);
        if ('html' === $this->_helper->contextSwitch()->getCurrentContext()) {
            //enable layout:
            $this->getHelper('layout')->enableLayout();
        } else {
            $this->getHelper('layout')->disableLayout();
        }
    }
    
    public function indexAction() {
        $context = $this->_helper->contextSwitch()->getCurrentContext();
        $format = $this->getRequest()->getParam('format');
        if ($context === null) { // trye to reset it via $format
            if ($format !== null) {
                if ('json' !== $format && 'html' !== $format) {
                    throw new Exception('Resource listing is implemented only for format json, jsonp or html', 404);
                } else {
                    $context = $format; 
                }
            } else {
                $context = 'html'; //default for index
            }
        }
        $request = $this->getPsrRequest();
        $api = $this->getDI()->make($this->fullNameResourceClass);
        $result = $api->fetchUriName($request);
        if ($context === 'html') {
           $this->getHelper('layout')->enableLayout();
           $this->view->resource = $result;
           return $this->renderScript($this->viewpath . 'index.phtml');
        } else { 
            if ($context==='json') {
            $this->_helper->contextSwitch()->setAutoJsonSerialization(false);
            $this->getResponse()->setBody(json_encode($result, JSON_UNESCAPED_SLASHES));
            } else { // th only left fileterd option is jsonp 
                $callback = $this->getRequest()->getParam('callback');
                $response = JsonpResponse::produceJsonPResponse($result, $callback);
                $this->emitResponse($response);
            }
        }
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
           $this->view->resource = $api->findResourceById($id);
           $this->view->resProperties = $this ->preparePropertiesForHTML($this->view->resource);
           return $this->renderScript($this->viewpath . 'get.phtml');
        } else {
            $response = $api->findResourceByIdResponse($request, $id, $context);
            $this->emitResponse($response);
        }
    }
    
    
    private function preparePropertiesForHTML($resource) {
        $props = $resource->getProperties();
        $retVal = [];
        $shortADR = RdfNamespace::shorten(vCard::ADR);
        $shortORG = RdfNamespace::shorten(vCard::ORG);
        foreach ($props as $propname => $vals) {
            $shortName = RdfNamespace::shorten($propname);
            if ($shortName !== $shortADR && $shortName !== $shortORG) {
                $shortHTMLName = $this->shortenForHTML($propname);
                $retVal[$shortHTMLName] = implode(', ', $vals);
                
            } else { // recursive elements of organisation
                if ($vals !== null && isset($vals) && is_array($vals))
                    if (count($vals) > 0) {
                        foreach ($vals[0]->getProperties() as $key => $val2) {
                            $shortName2 = $this->shortenForHTML($key);
                            $retVal[$shortName2] = implode(', ', $val2);
                        }
                    }
            }
        }
        return $retVal;
    }

    private function shortenForHTML($key) {
        $parts = RdfNamespace::splitUri($key, false);
        return $parts[1];
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