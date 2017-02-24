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
            $this->_501('Use <host>/public/api/status?format=json. For other than json formats: ');
        }
        
    }
    
     /**
     *
     * @apiVersion 1.0.0
     * @apiDescription Return a list of OpenSKOS concept statusses 
     * @api {get} /api/status Get OpenSKOS concept statusses
     * @apiName GetStatusses
     * @apiGroup Status
     *
     * @apiParam {String="json"}  format Other, than json, formats are not implemented (no need)
     * @apiSuccess (200) OK
     * @apiSuccessExample {String} Success-Response
     *   HTTP/1.1 200 OK 
     * ["candidate","approved","redirected","not_compliant","rejected","obsolete","deleted","expired"]
     *  
     */
    
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