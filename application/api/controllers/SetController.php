<?php

require_once 'AbstractController.php';

class Api_SetController extends AbstractController
{
   
    public function init()
    {
       parent::init();
       $this->fullNameResourceClass = 'OpenSkos2\Api\Set';
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

