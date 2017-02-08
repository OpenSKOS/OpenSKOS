<?php


use EasyRdf\RdfNamespace;
use OpenSkos2\Namespaces\vCard;

abstract class AbstractController extends OpenSKOS_Rest_Controller

{
    protected $fullNameResourceClass;
    protected $viewpath;
    public function init()
    {
        parent::init();
        $this->_helper->contextSwitch() ->initContext($this->getRequestedFormat());
        $this->getHelper('viewRenderer')->setNoRender(true);
        if ('html' === $this->_helper->contextSwitch()->getCurrentContext()) {
            $this->getHelper('layout')->enableLayout();
        } else {
            $this->getHelper('layout')->disableLayout();
        }
    }
    
    public function indexAction() {
        $params = $this->handleParams();
        $api = $this->getDI()->make($this->fullNameResourceClass);
        if ($params['shortlist']) { // needed for meertens browser
            $result = $api->mapNameSearchID();
            $this->_helper->contextSwitch()->setAutoJsonSerialization(true);
            return $this->getResponse()->setBody(json_encode($result, JSON_UNESCAPED_SLASHES));
        } else {
            if ($params['context'] === 'html') {
                $index= $api->mapNameSearchID();
                $this->getHelper('layout')->enableLayout();
                $this->view->resource = $index;
                return $this->renderScript($this->viewpath . 'index.phtml');
            } else {
                $response = $api->fetchDeatiledListResponse($params);
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
        $response = $api->delete($request);
        $this->emitResponse($response);
    }
    
    /*
     * Input: request parameters (which may or may not contan context, format, and have 1 and 0, "yes" and "no" for boolean values)
     * Output: a mapping parameter->value, where values are filled in as completely as possible and standatized (true/false for booleans) 
     */
    private function handleParams() {
        $retVal=[];
        $retVal['callback'] = null;
        $retVal['context'] = $this->_helper->contextSwitch()->getCurrentContext();
        $request = $this->getRequest();
        $format = $request->getParam('format');
        if ($retVal['context'] === null) { // try to reset it via $format
            if ($format !== null) {
                $retVal['context'] = $format; 
                }
            else {
                $retVal['context'] = 'rdf'; //default for index
            }
        }
        if ($retVal['context'] === 'jsonp') {
            $retVal['callback'] =  $request->getParam('callback');
        } 
        
        if ($request->getParam('shortlist') === null) {
           $retVal['shortlist']= false;
        } else {
            if ($request->getParam('shortlist') === 'true') {
                $retVal['shortlist']= true;
            } else {
                $retVal['shortlist'] = false;
            }
        }
        
       
        
        $allow_oai = $this->getRequest()->getParam('allow_oai');
        if (null !== $allow_oai) {
            switch (strtolower($allow_oai)) {
                case '1':
                case 'yes':
                case 'y':
                case 'true':
                    $retVal['allow_oai'] = 'true';
                    break;
                case '0':
                case 'no':
                case 'n':
                case 'false':
                    $retVal['allow_oai'] = 'false';
                    break;
            }
        } else {
            $retVal['allow_oai'] = null;
        }
        
        return $retVal;
    }
    
}