<?php


class Api_SkosCollectionController extends OpenSKOS_Rest_Controller
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
        if ('json' !== $this->_helper->contextSwitch()->getCurrentContext()) {
            $this->_501('This action, which lists the uris of all skos collections, is implemented only for json format output.');
        };
        $resourceManager = $this -> getResourceManager();
        $result = $resourceManager ->fetchSkosCollections();
        $this->_helper->contextSwitch()->setAutoJsonSerialization(false);
        $this->getResponse()->setBody(json_encode($result, JSON_UNESCAPED_SLASHES));
    }
    
    public function getAction()
    {
       $this->_501('get');
    }
    
    public function postAction()
    {
        $request = $this->getPsrRequest();
        $api = $this->getDI()->make('\OpenSkos2\Api\SkosCollection');
        $response = $api->create($request);
        $this->emitResponse($response);
    }
    
    public function putAction()
    {
        $this->_501('put');
    }
    
    public function deleteAction()
    {
        $request = $this->getPsrRequest();
        /* @var $relation \OpenSkos2\Api\Relation */
        $skoscollection = $this->getDI()->get('\OpenSkos2\Api\SkosCollection');
        $response = $skoscollection->deleteSkosCollection($request);
        $this->emitResponse($response);
    }
}