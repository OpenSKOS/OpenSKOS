<?php

use OpenSkos2\Namespaces\Dcterms;
use OpenSkos2\Namespaces\Dcmi;

require_once 'AbstractController.php';

class Api_SetController extends AbstractController
{
   
    public function init()
    {
       parent::init();
       $this -> resourceClass = 'Set';
       $this->fullNameResourceClass = 'OpenSkos2\Api\Set';
       $this ->indexProperty  = Dcterms::TITLE;
       $this -> rdfType = Dcmi::DATASET;
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

