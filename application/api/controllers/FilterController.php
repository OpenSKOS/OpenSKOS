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
   * @apiDescription Returns a list of filters (skos collection, concept scheme, set, tenant, status, relation types)
   * to facilitate concept or relation search via a frontend.
   * It is used to obtain lists of all the tenants, sets, skos collections, concept scheme’s, concepts and statuses by 
   * doing one API request instead of doing 6 requests. And it eventually amounts to a single request to a triple store,
   * via fetchResourceFilters() from ResourceManager.php.  It is useful for a frontend-browser to provide the lists of 
   * the tenants, sets, skos collections, concept schemes and statuses, so that the user can mark, say, some concept 
   * schemata and obtain all concepts from these selected schemata. Similarly, it is used for browsing relations where 
   * the user of the browser can pick up to show only some relations with targets (and or sources) form some particular 
   * schemes and/or skos collections.
 
   * @api {get} /api/filter Get OpenSKOS search filters 
   * @apiName GetFilters 
   * @apiGroup Filter
   *
   * @apiParam {String="json"}  format Other, than json, formats are not implemented (no need)
   * @apiParam {String="empty", "true", "false", "1", "0"}  relations Other, than json, formats are not implemented (no need). 
   *                                                              If set to true, returns the map qname-uri for all relation types, 
   *                                                              and a map title-uri for concept schemata to facliltate search for relation
   *                                                              instances of a given type with source and target for given schemata.
   * 
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
   *
   * [
   *  "candidate":"A newly added concept",
   *  "approved":"Candidate that was inspected and approved",
   *  "redirected":"Proposed concept was found to be better represented by another concept. The redirected concept will be maintained for convenience and will contain a forward note to the target concept.",
   *  "not_compliant":"Concept is not compliant with the GTAA standard, but is maintained for convenience of the creator. It can become obsolete when no longer necessary.",
   *  "rejected":"Substandard quality",
   *  "obsolete":"This concept is no longer necessary, may be succeeded by another concept.",
   *  "deleted":"All concept metadata is deleted.",
   * ]
   *
   *
   *    ["candidate", "approved", "redirected", "not_compliant", "rejected", "obsolete", "deleted", "expired"]
   * }
   *
   * @apiSuccessExample Success-Response for relations=true
   * HTTP/1.1 200 OK 
   * {
   * "http://www.w3.org/2002/07/owl#objectProperty": [
   *  {"uri":"http://menzo.org/xmlns#slower","title":"slower"},
   *  {"uri":"http://menzo.org/xmlns#faster","title":"faster"},
   *  {"uri":"http://www.w3.org/2004/02/skos/core#broader","title":"skos:broader"},
   *  {"uri":"http://www.w3.org/2004/02/skos/core#narrower","title":"skos:narrower"},
   *  {"uri":"http://www.w3.org/2004/02/skos/core#related","title":"skos:related"},
   *  {"uri":"http://www.w3.org/2004/02/skos/core#broaderTransitive","title":"skos:broaderTransitive"},
   *  {"uri":"http://www.w3.org/2004/02/skos/core#narrowerTransitive","title":"skos:narrowerTransitive"},
   *  {"uri":"http://www.w3.org/2004/02/skos/core#broadMatch","title":"skos:broadMatch"},
   *  {"uri":"http://www.w3.org/2004/02/skos/core#narrowMatch","title":"skos:narrowMatch"},
   *  {"uri":"http://www.w3.org/2004/02/skos/core#closeMatch","title":"skos:closeMatch"},
   *  {"uri":"http://www.w3.org/2004/02/skos/core#exactMatch","title":"skos:exactMatch"},
   *  {"uri":"http://www.w3.org/2004/02/skos/core#relatedMatch","title":"skos:relatedMatch"}
   *  ],
   *  "http://www.w3.org/2004/02/skos/core#ConceptScheme": [
   *   {"uri":"http://mertens/knaw/dataset_6c71d9c1-e4cc-4aa7-980c-cada7702e372/conceptscheme_e0ede522-61ff-4bbe-9547-4874d96d3251","title":"Schema-test"},
   *   {"uri":"http://openskos.meertens.knaw.nl/iso-639-3","title":"ISO 639-3"}
   *  ]
   *  }
   */
  public function indexAction() {
    $api = $this->getDI()->make('OpenSkos2\Api\Filters');
    $this->_helper->contextSwitch()->setAutoJsonSerialization(false);
    $rels = $this->getRequest()->getParam('relations');
    if ($rels === 'true' || $rels==="1") {
      $response = $api->fetchFiltersForRelations();
    } else {
      $response = $api->fetchFilters();
      $statusses = Concept::getAvailableStatusesWithDescriptions();
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
