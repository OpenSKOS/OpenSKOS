<?php

/*
 * OpenSKOS
 *
 * LICENSE
 *
 * This source file is subject to the GPLv3 license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @category   OpenSKOS
 * @package    OpenSKOS
 * @copyright  Copyright (c) 2015 Picturae (http://www.picturae.com)
 * @author     Picturae
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 */

require_once 'AbstractController.php';

/*
 * Relation is a resource as for instance, a concept or a chema. User-defined relation definitions are stored in triple store as any other resource.
 * There is a difference between a relation as a definition (works for used-defined relations) and relation as a triple. This is reflectied
 * in the naming of methods, e.g. deleteRelation means deleting the relation definition (if no correspondingly related concepts are detected)
 * and deleteRelationTriple amounts to updating corresponding related concepts.
 * 
 */

class API_RelationtypeController extends AbstractController {

  public function init() {
    parent::init();
    $this->fullNameResourceClass = 'OpenSkos2\Api\RelationType';
    $this->viewpath = "relationtype/";
  }

  /**
   *
   * @apiVersion 1.0.0
   * @apiDescription Get a detailed list OpenSKOS (non-SKOS) relation types
   *
   * @api {get} /api/relationtype Get OpenSKOS (non-KOS) relation types
   * @apiName GetRelationTypes
   * @apiGroup RelationType
   *
   * @apiParam {String=empty, "rdf","html","json","jsonp"}  format If set to jsonp, the request must contain a non-empty parameter "callback" as well
   * @apiParam {String} callback If format set to jsonp, must be non-empty 
   * 
   * @apiSuccess {String} StatusCode 200 OK.
   * @apiSuccessExample {xml+rdf} Success-Response:
   *   HTTP/1.1 200 OK
   * &lt;?xml version="1.0"?>
   * &lt;rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" 
   *          xmlns:dc="http://purl.org/dc/elements/1.1/" 
   *          xmlns:dcterms="http://purl.org/dc/terms/" 
   *          xmlns:skos="http://www.w3.org/2004/02/skos/core#" 
   *          xmlns:openskos="http://openskos.org/xmlns#" openskos:numFound="2" openskos:rows="5000" openskos:start="1">
   * &lt;rdf:Description xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#" rdf:about="http://menzo.org/xmlns#faster">
   *   &lt;rdf:type rdf:resource="http://www.w3.org/2002/07/owl#objectProperty"/>
   *    &lt;rdfs:subPropertyOf rdf:resource="http://www.w3.org/2004/02/skos/core#related"/>
   *    &lt;dcterms:dateSubmitted rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2017-02-27T11:34:49+00:00&lt;/dcterms:dateSubmitted>
   *    &lt;dcterms:creator rdf:resource="http://localhost/clavas/public/api/users/f122deab-755a-4f67-8502-7cd9bfd70ec5"/>
   *     &lt;dcterms:title>faster&lt;/dcterms:title>
   *   &lt;/rdf:Description>
   * &lt;rdf:Description xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#" rdf:about="http://menzo.org/xmlns#slower">
   *   &lt;rdf:type rdf:resource="http://www.w3.org/2002/07/owl#objectProperty"/>
   *   &lt;rdfs:subPropertyOf rdf:resource="http://www.w3.org/2004/02/skos/core#related"/>
   *   &lt;dcterms:dateSubmitted rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2017-02-24T18:36:03+00:00&lt;/dcterms:dateSubmitted>
   *   &lt;dcterms:creator rdf:resource="http://localhost/clavas/public/api/users/f122deab-755a-4f67-8502-7cd9bfd70ec5"/>
   *   &lt;dcterms:title xml:lang="nl">slower&lt;/dcterms:title>
   * &lt;/rdf:Description>
   * &lt;/rdf:RDF>
   * 
   */
  public function indexAction() {
    if ($this->getRequest()->getParam('format') === 'html') {
      $this->_501('INDEX for html format');
    }
    parent::indexAction();
  }

  /**
   *
   * @apiVersion 1.0.0
   * @apiDescription Get a specific OpenSKOS (non-SKOS) relation type 

   * @api {get} /api/relationtype an OpenSKOS (non-SKOS) relation type
   * @apiName GetRelationType
   * @apiGroup RelationType
   *
   * @apiParam {String}  id Relation type's uri, with # replaced by %23
   * @apiParam {String=empty, "rdf","html","json","jsonp"}  format If set to jsonp, the request must contain a non-empty parameter "callback" as well
   * @apiParam {String} callback If format set to jsonp, must be non-empty. 
   * 
   * @apiSuccess {String} StatusCode 200 OK.
   * @apiSuccessExample {xml+rdf} Success-Response:
   *   HTTP/1.1 200 OK
   * &lt;?xml version="1.0"?>
   * &lt;rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" 
   *          xmlns:dcterms="http://purl.org/dc/terms/" 
   *          xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#>
   * &lt;rdf:Description rdf:about="http://menzo.org/xmlns#faster">
   *   &lt;rdf:type rdf:resource="http://www.w3.org/2002/07/owl#objectProperty"/>
   *     &lt;rdfs:subPropertyOf rdf:resource="http://www.w3.org/2004/02/skos/core#related"/>
   *    &lt;dcterms:dateSubmitted rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2017-02-27T11:34:49+00:00&lt;/dcterms:dateSubmitted>
   *    &lt;dcterms:creator rdf:resource="http://localhost/clavas/public/api/users/f122deab-755a-4f67-8502-7cd9bfd70ec5"/>
   *     &lt;dcterms:title>faster&lt;/dcterms:title>
   *   &lt;/rdf:Description>
   * &lt;/rdf:RDF>
   * 
   */
  public function getAction() {
    $this->_helper->viewRenderer->setNoRender(true);
    $pairs = $this->getParam('pairs');
    if (isset($pairs) && $pairs === 'true') {
// lists all pairs of concepts in relation type with $params['id']
      $request = $this->getPsrRequest();
      $api = $this->getDI()->make($this->fullNameResourceClass);
      $response = $api->ListRelatedConceptPairs($request);
      $this->emitResponse($response);
    } else {
      $conceptUri = $this->getParam('conceptUri');
      if (isset($conceptUri)) {
// outputs all concepts-"targets" such that (conceptUri, relation, "target") holds if "isTarget=false" (default)
// outputs all concepts-"sources" such that ("source", relation, conceptUri) holds if "isTarget=true" 
        $request = $this->getPsrRequest();
        $api = $this->getDI()->make($this->fullNameResourceClass);
        $format = $this->getRequestedFormat();
        $response = $api->findRelatedConcepts($request, $conceptUri, $format);
        $this->emitResponse($response);
      } else {
        $id = $this->getParam('id');
        if (substr($id, 0, strlen('http://www.w3.org/2004/02/skos/core')) === 'http://www.w3.org/2004/02/skos/core') {
          throw new Exception('There is no relation description for skos relations', 404);
        }
        parent::getAction();
      }
    }
  }

  /**
   *
   * @apiVersion 1.0.0
   * @apiDescription Create a new OpenSKOS (non-SKOS) relation type 

   * The attribute rdf:about in the rdf:description element is abligatory. It is of the form &lt;namespace_uri>#&lt;title>.
   * The title is an obligatory element and must be unique within all relation types.
   *
   * @apiExample {String} Example request
   * <?xml version="1.0" encoding="UTF-8"?>
   * <rdf:RDF xmlns:rdf = "http://www.w3.org/1999/02/22-rdf-syntax-ns#"
   *          xmlns:openskos = "http://openskos.org/xmlns#"
   * xmlns:dcterms = "http://purl.org/dc/terms/">
   * <rdf:Description rdf:about="http://menzo.org/xmlns#slower">
   *        <dcterms:title>slower&lt;/dcterms:title>
   * </rdf:Description>
   * </rdf:RDF>
   *
   * @api {post} /api/relationtype Create an OpenSKOS relation type 
   * @apiName CreateRelationType
   * @apiGroup RelationType
   *
   * @apiParam {String} tenant The institute code for your institute in the OpenSKOS portal
   * @apiParam {String} key A valid API key
   * 
   * @apiSuccess (201) {String} Location relation-type uri.
   * @apiSuccessExample {xml+rdf} Success-Response:
   *   HTTP/1.1 201 Created
   * &lt;?xml version="1.0"?>
   * &lt;rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" 
   *    xmlns:dc="http://purl.org/dc/elements/1.1/" 
   *    xmlns:dcterms="http://purl.org/dc/terms/" 
   *    xmlns:skos="http://www.w3.org/2004/02/skos/core#">
   * &lt;rdf:Description xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#" 
   *   rdf:about="http://menzo.org/xmlns#slower">
   *   &lt;rdf:type rdf:resource="http://www.w3.org/2002/07/owl#objectProperty"/>
   *   &lt;rdfs:subPropertyOf rdf:resource="http://www.w3.org/2004/02/skos/core#related"/>
   *   &lt;dcterms:dateSubmitted rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2017-02-24T18:36:03+00:00&lt;/dcterms:dateSubmitted>
   *   &lt;dcterms:creator rdf:resource="http://localhost/clavas/public/api/users/f122deab-755a-4f67-8502-7cd9bfd70ec5"/>
   *   &lt;dcterms:title>slower&lt;/dcterms:title>
   * &lt;/rdf:Description>
   * &lt;/rdf:RDF>
   * 
   * @apiError MissingKey X-Error-Msg: No user key specified
   * @apiErrorExample MissingKey:
   *   HTTP/1.1 412 Precondition Failed
   *   No user key specified
   * 
   * @apiError MissingTenant X-Error-Msg:  No tenant specified
   * @apiErrorExample MissingTenant:
   *   HTTP/1.1 412 Precondition Failed
   *   No tenant specified
   * 
   * @apiError RelationTypeExists X-Error-Msg: The resource with uri &lt;id> already exists. Use PUT instead.
   * @apiErrorExample RelationTypeExists:
   *   HTTP/1.1 400 Bad request
   *   The resource with &lt;id> already exists. Use PUT instead.
   *
   * @apiError ValidationError X-Error-Msg: The resource of type http://www.w3.org/2002/07/owl#objectProperty with the property http://purl.org/dc/terms/title set to &lt;title> has been already registered.
   * @apiErrorExample ValidationError: 
   *   HTTP/1.1 400 Bad request
   *   The resource of type http://www.w3.org/2002/07/owl#objectProperty with the property http://purl.org/dc/terms/title set to &lt;title> has been already registered.
   *
   */
  public function postAction() {
    parent::postAction();
  }

  /**
   *
   * @apiVersion 1.0.0
   * @apiDescription Update an OpenSKOS (non-SKOS) relation type 

   * The attribute rdf:about in the rdf:description element is abligatory. It is of the form &lt;namespace_uri>#&lt;title>.
   * The title is an obligatory element and must be unique within all relation types.
   *
   * @apiExample {String} Example request
   * <?xml version="1.0" encoding="UTF-8"?>
   * <rdf:RDF xmlns:rdf = "http://www.w3.org/1999/02/22-rdf-syntax-ns#"
   *          xmlns:openskos = "http://openskos.org/xmlns#"
   *          xmlns:dcterms = "http://purl.org/dc/terms/">
   * <rdf:Description rdf:about="http://menzo.org/xmlns#better">
   *        <dcterms:title>warmer&lt;/dcterms:title>
   * </rdf:Description>
   * </rdf:RDF>
   *
   * @api {put} /api/relationtype Update an OpenSKOS (non-SKOS) relation type  
   * @apiName UpdateRelationType
   * @apiGroup RelationType
   *
   * @apiParam {String} tenant The institute code for your institute in the OpenSKOS portal
   * @apiParam {String} key A valid API key
   * 
   * @apiSuccess {String} StatusCode 200 Ok.
   * @apiSuccessExample {xml+rdf} Success-Response:
   *   HTTP/1.1 200 Created
   * &lt;?xml version="1.0"?>
   * &lt;rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" 
   *    xmlns:dc="http://purl.org/dc/elements/1.1/" 
   *    xmlns:dcterms="http://purl.org/dc/terms/">
   * &lt;rdf:Description rdf:about="http://menzo.org/xmlns#slower">
   *   &lt;rdf:type rdf:resource="http://www.w3.org/2002/07/owl#objectProperty"/>
   *   &lt;rdfs:subPropertyOf rdf:resource="http://www.w3.org/2004/02/skos/core#related"/>
   *   &lt;dcterms:contributor rdf:resource="http://localhost/clavas/public/api/users/f122deab-755a-4f67-8502-7cd9bfd70ec5"/>
   *   &lt;dcterms:modified rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2017-02-27T13:56:48+00:00&lt;/dcterms:modified>
   *   &lt;dcterms:dateSubmitted rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2017-02-24T18:36:03+00:00&lt;/dcterms:dateSubmitted>
   *   &lt;dcterms:creator rdf:resource="http://localhost/clavas/public/api/users/f122deab-755a-4f67-8502-7cd9bfd70ec5"/>
   *   &lt;dcterms:title>warmer&lt;/dcterms:title>
   * &lt;/rdf:Description>
   * &lt;/rdf:RDF>
   * 
   * @apiError MissingKey X-Error-Msg: No user key specified
   * @apiErrorExample MissingKey:
   *   HTTP/1.1 412 Precondition Failed
   *   No user key specified
   * 
   * @apiError MissingTenant X-Error-Msg:  No tenant specified
   * @apiErrorExample MissingTenant:
   *   HTTP/1.1 412 Precondition Failed
   *   No tenant specified
   *
   * @apiError ValidationError X-Error-Msg: The resource of type http://www.w3.org/2002/07/owl#objectProperty with the property http://purl.org/dc/terms/title set to &lt;title> has been already registered.
   * @apiErrorExample ValidationError: 
   *   HTTP/1.1 400 Bad request
   *   The resource of type http://www.w3.org/2002/07/owl#objectProperty with the property http://purl.org/dc/terms/title set to &lt;title> has been already registered.
   *
   */
  public function putAction() {
    parent::putAction();
  }

  /**
   *
   * @apiVersion 1.0.0
   * @apiDescription Delete an OpenSKOS (non-SKOS) relation type by its uri
   * 
   * @api {delete} /api/relationtype an OpenSKOS (non-SKOS) relation type
   * @apiName DeleteRelationType
   * @apiGroup RelationType
   *
   * @apiParam {String} tenant The institute code for your institute in the OpenSKOS portal
   * @apiParam {String} key A valid API key
   * @apiSuccess {String} StatusCode 200 OK.
   * @apiSuccessExample {xml+rdf} Success-Response:
   *    HTTP/1.1 200 OK
   * &lt;?xml version="1.0" encoding="utf-8" ?>
   * &lt;rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
   *       xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#"
   *       xmlns:dcterms="http://purl.org/dc/terms/">
   *  &lt;rdf:Description rdf:about="http://menzo.org/xmlns#better"">
   *     &lt;rdf:type rdf:resource="http://www.w3.org/2002/07/owl#objectProperty"/>
   *     &lt;dcterms:contributor rdf:resource="http://localhost/clavas/public/api/users/f122deab-755a-4f67-8502-7cd9bfd70ec5"/>
   *    &lt;dcterms:modified rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2017-02-27T13:56:48+00:00&lt;/dcterms:modified>
   *     &lt;dcterms:dateSubmitted rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2017-02-27T13:47:36+00:00&lt;/dcterms:dateSubmitted>
   *     &lt;dcterms:creator rdf:resource="http://localhost/clavas/public/api/users/f122deab-755a-4f67-8502-7cd9bfd70ec5"/>
   *     &lt;dcterms:description xml:lang="nl">example1&lt;/dcterms:description>
   *     &lt;dcterms:title>warm&lt;/dcterms:title>
   *   &lt;/rdf:Description>
   * &lt;/rdf:RDF>
   * 
   * @apiError Not found X-Error-Msg: The requested resource &lt;uri> of type http://www.w3.org/2002/07/owl#objectProperty was not found in the triple store.
   * @apiErrorExample NotFound
   *   HTTP/1.1 404 NotFound
   *   The requested resource &lt;uri> of type http://www.w3.org/2002/07/owl#objectProperty was not found in the triple store.
   * 
   * @apiError MissingKey X-Error-Msg: No user key specified
   * @apiErrorExample MissingKey:
   *   HTTP/1.1 412 Precondition Failed
   *   No user key specified
   * 
   * @apiError MissingTenant X-Error-Msg: No tenant specified
   * @apiErrorExample MissingTenant:
   *   HTTP/1.1 412 Precondition Failed
   *   No tenant specified
   */
  public function deleteAction() {
    parent::deleteAction();
  }

}
