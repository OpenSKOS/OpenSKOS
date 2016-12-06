<?php
// meertens was here

use OpenSkos2\Concept;

class Api_StatusController extends OpenSKOS_Rest_Controller
{
    public function init()
    {
       parent::init();
       $this ->viewpath="status/";
        $this->_helper->contextSwitch()
            ->initContext($this->getRequestedFormat());
        
        if ('json' != $this->_helper->contextSwitch()->getCurrentContext()) {
            $this->_501('Use <host>/public/api/status?format=html. For other than json formats: ');
        }
        
    }
    
    public function indexAction()
    {
        $hardcodedList = Concept::getAvailableStatuses();
        $this->_helper->contextSwitch()->setAutoJsonSerialization(false);
        $this->getResponse()->setBody(json_encode($hardcodedList));
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