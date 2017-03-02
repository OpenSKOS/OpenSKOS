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

  /**
   *
   * @apiVersion 1.0.0
   * @apiDescription Return a map from OpenSKOS concept search filter types (collection, concept scheme, set, tenant, status
   * to the lists of corresponding literal values or uris (with titels or codes when applicable)
   *   
   * @api {get} /api/filter Get OpenSKOS concept-search filters
   * @apiName GetFilters 
   * @apiGroup Filter
   *
   * @apiParam {String="json"}  format Other, than json, formats are not implemented (no need)
   * @apiSuccess {json} Body
   * @apiSuccessExample Success-Response
   *   HTTP/1.1 200 OK 
   * {"http://www.w3.org/2004/02/skos/core#Collection": [
   *     {"uri":"http://hdl.handle.net/11148/backendname_collection_b4f030d2-fd31-4987-93e2-4dc9d3f9e3ea", "title":"SkosCollection1"}
   * ], 
   * "http://www.w3.org/2004/02/skos/core#ConceptScheme":[
   *     {"uri":"http://mertens/knaw/dataset_6c71d9c1-e4cc-4aa7-980c-cada7702e372/conceptscheme_e0ede522-61ff-4bbe-9547-4874d96d3251", "title":"Schema-test"}
   * ], 
   * "http://purl.org/dc/dcmitype#Dataset":[
   *    {"uri":"http://mertens/knaw/dataset_6c71d9c1-e4cc-4aa7-980c-cada7702e372", "title":"Clavas Laguages"}, 
   *    {"uri":"http://mertens/knaw/dataset_96036967-5215-413a-a3bc-f4c07b14c16b", "title":"Clavas Organisations"},
   *    {"uri":"http://hdl.handle.net/11148/backendname_dataset_3c30c1e5-9e55-44a2-9735-82a5d9a34336", "title":"clavas set 3"}
   * ], 
   * "http://www.w3.org/ns/org#FormalOrganization":[
   *   {"uri":"http://mertens/knaw/formalorganization_bd9df26b-313c-445a-ab4e-3467b0429494", "title":"example.com"}
   * ], 
   * "http://openskos.org/xmlns#status":
   *    ["candidate", "approved", "redirected", "not_compliant", "rejected", "obsolete", "deleted", "expired"]
   * }
   *
   */
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
