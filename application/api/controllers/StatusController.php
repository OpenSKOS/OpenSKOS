<?php
// meertens was here

use OpenSkos2\Namespaces\OpenSkos as OpenSkosNamespace;
use OpenSkos2\Concept;

class Api_StatusController extends OpenSKOS_Rest_Controller
{
    public function init()
    {
       parent::init();
       $this ->viewpath="status/";
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
        $listFromJena = $resourceManager ->fetchObjectsWithProperty(OpenSkosNamespace::STATUS, 'Literal');
        $hardcodedList = Concept::getAvailableStatuses();
        //var_dump($hardcodedList);
        $result=array_values(array_unique(array_merge($listFromJena, $hardcodedList)));
        $this->_helper->contextSwitch()->setAutoJsonSerialization(false);
        $this->getResponse()->setBody(json_encode($result));
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