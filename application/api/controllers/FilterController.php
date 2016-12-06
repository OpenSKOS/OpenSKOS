<?php

use OpenSkos2\Concept;
use OpenSkos2\Namespaces\OpenSkos;

class Api_FilterController extends OpenSKOS_Rest_Controller {

  protected $viewpath = "filter/";

  public function init() {
    parent::init();
    if ('json' != $this->_helper->contextSwitch()->getCurrentContext()) {
      $this->_501('Use <host>/public/api/filter?format=json. For other than json formats: ');
    }
    $this->getHelper('viewRenderer')->setNoRender(true);
    $this->getHelper('layout')->disableLayout();
  }

  public function indexAction() {
    $api = $this->getDI()->make('OpenSkos2\Api\Filters');
    $this->_helper->contextSwitch()->setAutoJsonSerialization(false);
    $rels = $this->getRequest()->getParam('relations');
    if ($rels === 'true') {
      $response = $api->fetchFiltersForRelations();
    } else {
      $response = $api->fetchFilters();
      $statusses = Concept::getAvailableStatuses();
      $response[OpenSkos::STATUS] = $statusses;
    }
    return $this->getResponse()->setBody(json_encode($response, JSON_UNESCAPED_SLASHES));
  }

  public function getAction() {
    
  }

  public function postAction() {
    
  }

  public function putAction() {
    
  }

  public function deleteAction() {
    
  }

}
