<?php

require_once 'AbstractController.php';

class Api_ConceptSchemeController extends AbstractController
{
   
     public function init()
    {
       parent::init();
       $this->fullNameResourceClass = 'OpenSkos2\Api\ConceptScheme';
      
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
