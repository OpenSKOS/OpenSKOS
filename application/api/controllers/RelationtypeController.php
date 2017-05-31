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
    $this->apiResourceClass = 'OpenSkos2\Api\RelationType';
    $this->viewpath = "relationtype/";
  }

  /**
   *
   * @apiVersion 1.0.0
   * @apiDescription If the parameter "shortlits" is not set or set to "false" then descriptions of the OpenSKOs (non-SKOS) relation types will be listed in the response. Othewrise the json map name-uri for all relation types will be in the response body.
   * 
   * in RDF: /api/relationtype/  or /api/relationtype?format=rdf
   * 
   * in JSON: /api/relationtype?format=json
   * 
   * in JSONP: /api/relationtype?format=jsonp&callback=myCallback1234
   * 
   * in HTML: /api/relationtype?format=html
   *
   * in JSON as name-uri map (SKOS relation types are included): /api/relationtype?shortlist=true&format=json
   * 
   *  
   * @api {get} /api/relationtype Get OpenSKOS relation types 
   * @apiName GetRelationTypes
   * @apiGroup RelationType
   *
   * @apiParam {String=empty, "rdf","html","json","jsonp"}  format If set to jsonp, the request must contain a non-empty parameter "callback" as well
   * @apiParam {String} callback If format set to jsonp, must be non-empty 
   * @apiParam {String=empty, true, false, 1, 0} shortlist If set to true, then format must be set to json
   * 
   *  
   * @apiSuccess {xml/json/jsonp/html} Body
   * @apiSuccessExample Success-Response:
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
   * @apiSuccessExample Success-Response for shortlist=true:
   * HTTP/1.1 200 OK
   * {
   *  "skos:broader":"http://www.w3.org/2004/02/skos/core#broader",
   *  "skos:narrower":"http://www.w3.org/2004/02/skos/core#narrower",
   *  "skos:related":"http://www.w3.org/2004/02/skos/core#related",
   *  "skos:broaderTransitive":"http://www.w3.org/2004/02/skos/core#broaderTransitive",
   *  "skos:narrowerTransitive":"http://www.w3.org/2004/02/skos/core#narrowerTransitive",
   *  "skos:broadMatch":"http://www.w3.org/2004/02/skos/core#broadMatch",
   *  "skos:narrowMatch":"http://www.w3.org/2004/02/skos/core#narrowMatch",
   *  "skos:closeMatch":"http://www.w3.org/2004/02/skos/core#closeMatch",
   *  "skos:exactMatch":"http://www.w3.org/2004/02/skos/core#exactMatch",
   *  "skos:relatedMatch":"http://www.w3.org/2004/02/skos/core#relatedMatch",
   *  "menzo:faster":"http://menzo.org/xmlns#faster",
   *  "menzo:slower":"http://menzo.org/xmlns#slower",
   *  "menzo:longer":"http://menzo.org/xmlns#longer"
   * }
   */
  public function indexAction() {
    if ($this->getRequest()->getParam('format') === 'html') {
      $this->_501('INDEX for html format');
    }
    return parent::indexAction();
  }

  /**
   *
   * @apiVersion 1.0.0
   * @apiDescription A relation-type description is produced in response only for non-SKOS, i.e. "user-defined", relation types and f the request parameter "memebrs" is not set (default) or set to "false". If thsi parameter is set to "true" then the instances of this relation type are produces in json format.
   *
   * in RDF: or /api/relationtype?id={uri}
   * 
   * or /api/relationtype?id={uri}&format=rdf
   * 
   * in JSON:  /api/relationtype?id={uri}&format=json
   * 
   * in JSONP: /api/relationtype?id={uri}&format=jsonp&callback=myCallback1234
   * 
   * in HTML: /api/relationtype?id={uri}&format=html
   * 
   * in JSON to obtain the list of instances for a given relation type:  /api/relationtype?id={uri}&members=true&format=json
   *
   * in RDF to obtain the list of source concepts for a given relation type and conceptUri: /api/relationtype?id={uri}&conceptUri={uri}&isTarget=true
   * 
   * or  /api/relationtype?id={uri}&conceptUri={uri}&isTarget=true&format=rdf
   * 
   * in JSON to obtain the list of source concepts for a given relation type and conceptUri: /api/relationtype?id={uri}&conceptUri={uri}&isTarget=true&format=json
   *
   * in JSONP to obtain the list of source concepts for a given relation type and conceptUri: /api/relationtype?id={uri}&conceptUri={uri}&isTarget=true&format=jsonp&callback=myCallback123
   * 
   * in RDF to obtain the list of target concepts for a given relation type and conceptUri: /api/relationtype?id={uri}&conceptUri={uri}&isTarget=true
   * 
   * or  /api/relationtype?id={uri}&conceptUri={uri}&format=rdf
   * 
   * in JSON to obtain the list of target concepts for a given relation type and conceptUri: /api/relationtype?id={uri}&conceptUri={uri}&format=json
   *
   * in JSONP to obtain the list of target concepts for a given relation type and conceptUri: /api/relationtype?id={uri}&conceptUri={uri}&format=jsonp&callback=myCallback123
   *
   * @api {get} /api/relationtype Get a specific OpenSKOS relation type 
   * @apiName GetRelationType
   * @apiGroup RelationType
   *
   * @apiParam {String}  id Relation type's uri, with # replaced by %23 
   * @apiParam {String=empty, "rdf","html","json","jsonp"}  format If set to jsonp, the request must contain a non-empty parameter "callback" as well
   * @apiParam {String} callback If format set to jsonp, must be non-empty. 
   * @apiParam {String=empty, "true", "false", "1", "0"} members If set to ture then returns the list of triples subject-property-object, 
   *                                                             which are instances of the relation type with the given id. The subject and object are shortened concept descriptions.
   *                                                             The property is the given id (uri) of the relation type. The response body is always in json format.
   * @apiParam {String} conceptUri The response contains all concepts-"targets" such that (conceptUri, relation-type id, "target") holds if "isTarget=false" (default) otherwise the response contains all concepts-"sources" such that ("source", relation, conceptUri) holds. 
   * @apiParam {String=empty, "true", "false", "1", "0"} isTarget Goes in the pair with the conceptUri parameter, see its description or detail.
   * 
   * 
   * 
   * @apiSuccess {xml/json/jsonp/html} Body
   * @apiSuccessExample Success-Response:
   *   HTTP/1.1 200 OK
   * &lt;?xml version="1.0"?>
   * &lt;rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" 
   *          xmlns:dcterms="http://purl.org/dc/terms/" 
   *          xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#>
   * &lt;rdf:Description rdf:about="http://menzo.org/xmlns#faster">
   *   &lt;rdf:type rdf:resource="http://www.w3.org/2002/07/owl#objectProperty"/>
   *    &lt;rdfs:subPropertyOf rdf:resource="http://www.w3.org/2004/02/skos/core#related"/>
   *    &lt;dcterms:dateSubmitted rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2017-02-27T11:34:49+00:00&lt;/dcterms:dateSubmitted>
   *    &lt;dcterms:creator rdf:resource="http://localhost/clavas/public/api/users/f122deab-755a-4f67-8502-7cd9bfd70ec5"/>
   *    &lt;dcterms:title>faster&lt;/dcterms:title>
   *   &lt;/rdf:Description>
   * &lt;/rdf:RDF>
   * 
   * @apiSuccessExample Success-Response for members=true:
   *   HTTP/1.1 200 OK
   * [
   * {"s":{"uuid":"d28664a5-1d63-41cb-80a9-a185052f7c1d","prefLabel":"Loup A","lang":"en","schema_title":"ISO 639-3","schema_uri":"http://openskos.meertens.knaw.nl/iso-639-3"},
   *  "p":"http://www.w3.org/2004/02/skos/core#broader",
   *  "o":{"uuid":"1577482d-58ac-42d6-aac0-c6cfb3d9e513","prefLabel":"Linear A","lang":"en","schema_title":"ISO 639-3","schema_uri":"http://openskos.meertens.knaw.nl/iso-639-3"}
   * },
   * {"s":{"uuid":"d28664a5-1d63-41cb-80a9-a185052f7c1d","prefLabel":"Loup A","lang":"en","schema_title":"ISO 639-3","schema_uri":"http://openskos.meertens.knaw.nl/iso-639-3"},
   *  "p":"http://www.w3.org/2004/02/skos/core#broader",
   *  "o":{"uuid":"a0514643-50e4-426a-838a-f767d1cc1cfd","prefLabel":"Tokharian A","lang":"en","schema_title":"ISO 639-3","schema_uri":"http://openskos.meertens.knaw.nl/iso-639-3"}
   * }
   * ]
   * 
   *  @apiSuccessExample Success-Response for conceptUri=&lt;uri>&isTarget=true
   *   HTTP/1.1 200 OK
   *   &lt;?xml version="1.0"?>
   *   &lt;rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" 
   *    xmlns:dc="http://purl.org/dc/elements/1.1/" 
   *    xmlns:dcterms="http://purl.org/dc/terms/" 
   *    xmlns:skos="http://www.w3.org/2004/02/skos/core#" 
   *    xmlns:openskos="http://openskos.org/xmlns#" 
   *    openskos:numFound="2" openskos:rows="5000" openskos:start="0">
   *       &lt;rdf:Description rdf:about="http://cdb.iso.org/lg/CDB-00138365-001">
   *           &lt;rdf:type rdf:resource="http://www.w3.org/2004/02/skos/core#Concept"/>
   *           &lt;dcterms:creator rdf:resource="http://localhost/clavas/public/api/users/f122deab-755a-4f67-8502-7cd9bfd70ec5"/>
   *           &lt;dcterms:dateSubmitted rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2017-03-02T11:22:07+00:00&lt;/dcterms:dateSubmitted>
   *           &lt;openskos:status>candidate&lt;/openskos:status>
   *           &lt;skos:prefLabel xml:lang="en">Linear A&lt;/skos:prefLabel>
   *           &lt;dcterms:source>http://www.sil.org/iso639-3/documentation.asp?id=lab&lt;/dcterms:source>
   *           &lt;skos:inScheme rdf:resource="http://openskos.meertens.knaw.nl/iso-639-3"/>
   *           &lt;skos:notation>lab&lt;/skos:notation>
   *           &lt;skos:narrower rdf:resource="http://cdb.iso.org/lg/CDB-00138139-001"/>
   *           &lt;openskos:uuid>1577482d-58ac-42d6-aac0-c6cfb3d9e513&lt;/openskos:uuid>
   *      &lt;/rdf:Description>
   *     &lt;rdf:Description rdf:about="http://cdb.iso.org/lg/CDB-00138395-001">
   *          &lt;rdf:type rdf:resource="http://www.w3.org/2004/02/skos/core#Concept"/>
   *          &lt;dcterms:source>http://www.sil.org/iso639-3/documentation.asp?id=xto&lt;/dcterms:source>
   *          &lt;dcterms:creator rdf:resource="http://localhost/clavas/public/api/users/f122deab-755a-4f67-8502-7cd9bfd70ec5"/>
   *          &lt;skos:narrower rdf:resource="http://cdb.iso.org/lg/CDB-00138139-001"/>
   *          &lt;openskos:status>candidate&lt;/openskos:status>
   *          &lt;skos:inScheme rdf:resource="http://openskos.meertens.knaw.nl/iso-639-3"/>
   *          &lt;openskos:uuid>a0514643-50e4-426a-838a-f767d1cc1cfd&lt;/openskos:uuid>
   *          &lt;dcterms:dateSubmitted rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2017-03-02T11:36:39+00:00&lt;/dcterms:dateSubmitted>
   *          &lt;skos:prefLabel xml:lang="en">Tokharian A&lt;/skos:prefLabel>
   *         &lt;skos:notation>xto&lt;/skos:notation>
   *      &lt;/rdf:Description>
   *  &lt;/rdf:RDF>
   *
   * 
   * 
   * @apiError NotFound There is no relation-type description for skos relation types
   * @apiErrorExample MissingTenant
   *   HTTP/1.1 404 Not Found
   *   There is no relation-type description for skos relation types
   */
  public function getAction() {
    $this->_helper->viewRenderer->setNoRender(true);
    $members = $this->getParam('members');
    if (isset($members) && ($members === 'true' || $members === "1")) {
// lists all pairs of concepts in relation type with $params['id']
      $request = $this->getPsrRequest();
      $api = $this->getDI()->make($this->apiResourceClass);
      $response = $api->listRelatedConceptPairs($request);
      $this->emitResponse($response);
    } else {
      $conceptUri = $this->getParam('conceptUri');
      if (isset($conceptUri)) {
// outputs all concepts-"targets" such that (conceptUri, relation, "target") holds if "isTarget=false" (default)
// outputs all concepts-"sources" such that ("source", relation, conceptUri) holds if "isTarget=true" 
        $request = $this->getPsrRequest();
        $api = $this->getDI()->make($this->apiResourceClass);
        $format = $this->getRequestedFormat();
        $response = $api->findRelatedConcepts($request, $conceptUri, $format);
        $this->emitResponse($response);
      } else {
        $id = $this->getParam('id');
        if (substr($id, 0, strlen('http://www.w3.org/2004/02/skos/core')) === 'http://www.w3.org/2004/02/skos/core') {
          throw new Exception('There is no relation-type description for skos relation types', 404);
        }
        parent::getAction();
      }
    }
  }

  /**
   *
   * @apiVersion 1.0.0
   * @apiDescription Create a new OpenSKOS (non-SKOS) relation type 
   *
   * The attribute rdf:about in the rdf:description element is obligatory. It is of the form &lt;namespace_uri>#&lt;title>.
   * The title is an obligatory element and must be unique within all relation types.
   *
   * @apiExample {String} Example request
   * <?xml version="1.0" encoding="UTF-8"?>
   * <rdf:RDF xmlns:rdf = "http://www.w3.org/1999/02/22-rdf-syntax-ns#"
   *          xmlns:openskos = "http://openskos.org/xmlns#"
   * xmlns:dcterms = "http://purl.org/dc/terms/">
   * <rdf:Description rdf:about="http://menzo.org/xmlns#slower">
   *        <dcterms:title>slower</dcterms:title>
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
   * @apiSuccess (Created 201) {String} Location relation-type uri
   * @apiSuccess (Created 201) {xml} Body
   * @apiSuccessExample Success-Response:
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
   * @apiError MissingKey No user key specified
   * @apiErrorExample MissingKey
   *   HTTP/1.1 412 Precondition Failed
   *   No user key specified
   * 
   * @apiError MissingTenant No tenant specified
   * @apiErrorExample MissingTenant
   *   HTTP/1.1 412 Precondition Failed
   *   No tenant specified
   * 
   * @apiError RelationTypeExists The resource with uri &lt;id> already exists. Use PUT instead.
   * @apiErrorExample RelationTypeExists:
   *   HTTP/1.1 400 Bad request
   *   The resource with &lt;id> already exists. Use PUT instead.
   *
   * @apiError ValidationError The resource of type http://www.w3.org/2002/07/owl#objectProperty with the property http://purl.org/dc/terms/title set to &lt;title> has been already registered.
   * @apiErrorExample ValidationError 
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

   * The attribute rdf:about in the rdf:description element is obligatory. It is of the form &lt;namespace_uri>#&lt;title>.
   * The title is an obligatory element and must be unique within all relation types.
   *
   * @apiExample {String} Example request
   * <?xml version="1.0" encoding="UTF-8"?>
   * <rdf:RDF xmlns:rdf = "http://www.w3.org/1999/02/22-rdf-syntax-ns#"
   *          xmlns:openskos = "http://openskos.org/xmlns#"
   *          xmlns:dcterms = "http://purl.org/dc/terms/">
   * <rdf:Description rdf:about="http://menzo.org/xmlns#better">
   *        <dcterms:title>warmer</dcterms:title>
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
   * @apiSuccess {xml} Body
   * @apiSuccessExample Success-Response:
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
   *   &lt;dcterms:contributor rdf:resource="http://localhost/clavas/public/api/users/f122deab-755a-4f67-8502-7cd9bfd70ec5"/>
   *   &lt;dcterms:modified rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2017-02-27T13:56:48+00:00&lt;/dcterms:modified>
   * &lt;/rdf:Description>
   * &lt;/rdf:RDF>
   * 
   * @apiError MissingKey No user key specified
   * @apiErrorExample MissingKey
   *   HTTP/1.1 412 Precondition Failed
   *   No user key specified
   * 
   * @apiError MissingTenant No tenant specified
   * @apiErrorExample MissingTenant
   *   HTTP/1.1 412 Precondition Failed
   *   No tenant specified
   *
   * @apiError ValidationError The resource of type http://www.w3.org/2002/07/owl#objectProperty with the property http://purl.org/dc/terms/title set to &lt;title> has been already registered.
   * @apiErrorExample ValidationError 
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
   * @api {delete} /api/relationtype Delete an OpenSKOS (non-SKOS) relation type
   * @apiName DeleteRelationType
   * @apiGroup RelationType
   *
   * @apiParam {String} tenant The institute code for your institute in the OpenSKOS portal
   * @apiParam {String} key A valid API key
   * @apiSuccess {xml} Body
   * @apiSuccessExample Success-Response:
   *    HTTP/1.1 200 OK
   * &lt;?xml version="1.0" encoding="utf-8" ?>
   * &lt;rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
   *       xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#"
   *       xmlns:dcterms="http://purl.org/dc/terms/">
   *  &lt;rdf:Description rdf:about="http://menzo.org/xmlns#better"">
   *     &lt;rdf:type rdf:resource="http://www.w3.org/2002/07/owl#objectProperty"/>
   *     &lt;dcterms:contributor rdf:resource="http://localhost/clavas/public/api/users/f122deab-755a-4f67-8502-7cd9bfd70ec5"/>
   *     &lt;dcterms:modified rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2017-02-27T13:56:48+00:00&lt;/dcterms:modified>
   *     &lt;dcterms:dateSubmitted rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2017-02-27T13:47:36+00:00&lt;/dcterms:dateSubmitted>
   *     &lt;dcterms:creator rdf:resource="http://localhost/clavas/public/api/users/f122deab-755a-4f67-8502-7cd9bfd70ec5"/>
   *     &lt;dcterms:description xml:lang="nl">example1&lt;/dcterms:description>
   *     &lt;dcterms:title>warm&lt;/dcterms:title>
   *   &lt;/rdf:Description>
   * &lt;/rdf:RDF>
   * 
   * @apiError NotFound The requested resource &lt;uri> of type http://www.w3.org/2002/07/owl#objectProperty was not found in the triple store.
   * @apiErrorExample NotFound
   *   HTTP/1.1 404 NotFound
   *   The requested resource &lt;uri> of type http://www.w3.org/2002/07/owl#objectProperty was not found in the triple store.
   * 
   * @apiError MissingKey No user key specified
   * @apiErrorExample MissingKey
   *   HTTP/1.1 412 Precondition Failed
   *   No user key specified
   * 
   * @apiError MissingTenant No tenant specified
   * @apiErrorExample MissingTenant
   *   HTTP/1.1 412 Precondition Failed
   *   No tenant specified
   */
  public function deleteAction() {
    parent::deleteAction();
  }

}
