<?php

// meertens was here
use OpenSkos2\Namespaces\Skos as SkosNamespace;

class Api_SchemaController extends AbstractResourceController
{
   
    public function indexAction()
    {
        $resourceManager = $this -> getResourceManager();
        $result = $resourceManager ->fetchObjectsWithProperty(SkosNamespace::INSCHEME, 'Resource');
        $this->_helper->contextSwitch()->setAutoJsonSerialization(false);
        $this->getResponse()->setBody(json_encode($result, JSON_UNESCAPED_SLASHES));
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
