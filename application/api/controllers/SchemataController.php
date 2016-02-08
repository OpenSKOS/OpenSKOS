<?php

// meertens was here
use OpenSkos2\Namespaces\Skos as SkosNamespace;

class Api_SchemataController extends OpenSKOS_Rest_Controller
{
    public function init()
    {
        parent::init();
        $this->_helper->contextSwitch()
            ->initContext($this->getRequestedFormat());
        
        if ('html' == $this->_helper->contextSwitch()->getCurrentContext()) {
            //enable layout:
            $this->getHelper('layout')->enableLayout();
        }
        
    }
    
    public function indexAction()
    {
        $resourceManager = $this -> getResourceManager();
        $result = $resourceManager ->fetchFacets(SkosNamespace::INSCHEME, 'Resource');
        $this->_helper->contextSwitch()->setAutoJsonSerialization(false);
        $this->getResponse()->setBody($result);
    }
    
    public function getAction()
    {
        $this->_501('get');
    }
    
    public function postAction()
    {
        $this->_501('post');
    }
    
    public function putAction()
    {
        $this->_501('put');
    }
    
    public function deleteAction()
    {
        $this->_501('delete');
    }
}
