<?php

require_once 'AbstractController.php';

class Api_SkoscollectionController extends AbstractController
{
     public function init()
    {
       parent::init();
       $this->fullNameResourceClass = 'OpenSkos2\Api\SkosCollection';
      
    }
    
     public function indexAction()
    {
       parent::indexAction();
    }
   
    public function getAction()
    {
        parent::getAction();
    }
    
    public function postAction()
    {
       parent::postAction();
    }
    
    public function putAction()
    {
        parent::putAction();
    }
    
    public function deleteAction()
    {
        parent::deleteAction();
    }
}