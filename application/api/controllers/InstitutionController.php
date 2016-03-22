<?php

use OpenSkos2\Namespaces\vCard;

require_once 'AbstractController.php';

class Api_InstitutionController extends AbstractController
{
    public function init()
    {
       parent::init();
       $this -> resourceClass = 'Tenant';
       $this->fullNameResourceClass = 'OpenSkos2\Api\Tenant';
       $this ->indexProperty  = vCard::ORGNAME;
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
