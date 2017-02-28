<?php

require_once 'AbstractController.php';

class Api_ConceptschemeController extends AbstractController
{
   
     public function init()
    {
       parent::init();
       $this->fullNameResourceClass = 'OpenSkos2\Api\ConceptScheme';
       $this ->viewpath="conceptscheme/";
    }
    
    /**
     *
     * @apiVersion 1.0.0
     * @apiDescription Get SKOS Concept Schemata
    
     * Get a detailed list of SKOS concept schemata
     *
     * @api {get} /api/conceptscheme Get SKOS concept scheme
     * @apiName GetConceptSchemata
     * @apiGroup ConceptScheme
     *
     * @apiParam {String=empty, "rdf","html","json","jsonp"}  format If set to jsonp, the request must contain a non-empty parameter "callback" as well
     * @apiParam {String} callback If format set to jsonp, must be non-empty 
     * 
     * @apiSuccess {String} OK
     * @apiSuccessExample {xml+rdf} Success-Response:
     *   HTTP/1.1 200 OK
     * &lt;?xml version="1.0"?>
     * &lt;rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" 
     *  xmlns:dc="http://purl.org/dc/elements/1.1/" 
     *  xmlns:dcterms="http://purl.org/dc/terms/" 
     *  xmlns:skos="http://www.w3.org/2004/02/skos/core#"
     *  xmlns:openskos="http://openskos.org/xmlns#" 
     *  openskos:numFound="1" openskos:rows="5000" openskos:start="1">
     * &lt;rdf:Description rdf:about="http://mertens/knaw/dataset_5980699b-2c9a-4717-ac30-aed13743cc84/conceptscheme_fed05e8d-f586-45b5-934a-7e8fccb61871">
    *  &lt;rdf:type rdf:resource="http://www.w3.org/2004/02/skos/core#ConceptScheme"/>
    *    &lt;dcterms:dateSubmitted rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2017-02-21T17:52:54+00:00&lt;/dcterms:dateSubmitted>
    *     &lt;dcterms:creator rdf:resource="http://localhost/clavas/public/api/users/26272b05-6833-4ace-8e36-5b650fcefcdb"/>
    *     &lt;openskos:set rdf:resource="http://mertens/knaw/dataset_5980699b-2c9a-4717-ac30-aed13743cc84"/>
    *     &lt;dcterms:description xml:lang="nl">example1&lt;/dcterms:description>
    *     &lt;dcterms:title xml:lang="nl">Schema 1&lt;/dcterms:title>
    *     &lt;openskos:uuid>fed05e8d-f586-45b5-934a-7e8fccb61871&lt;/openskos:uuid>
    *     &lt;openskos:tenant rdf:resource="http://mertens/knaw/formalorganization_10302a0e-7e4e-4dbb-bce0-59e2a21c8785"/>
    *   &lt;/rdf:Description>
    * &lt;/rdf:RDF>
    * 
    */
     public function indexAction()
    {
       parent::indexAction();
    }
   
    /**
     *
     * @apiVersion 1.0.0
     * @apiDescription Return a  specific SKOS Concept Scheme by its uri or uuid
     *
     * @api {get} /api/conceptscheme/ Get SKOS concept scheme details by its id (which is set to the set's uri or uuid) as a request parameter
     * @api {get} /api/conceptscheme/{uuid}[.rdf, .html, .json, .jsonp] Get SKOS concept scheme details
     * @apiName GetConceptScheme
     * @apiGroup conceptScheme
     *
     * @apiParam {String} id (uuid or uri)
     * @apiParam {String=empty, "rdf","html","json","jsonp"}  format If set to jsonp, must contain parameter callback as well
     * @apiParam {String} callback If format set to jsonp, must be non-empty
     * 
     * @apiSuccess {String} StatusCode 200 OK.
     * @apiSuccessExample {xml+rdf} Success-Response:
     *   HTTP/1.1 200 OK
     * &lt;?xml version="1.0" encoding="utf-8" ?>
     * &lt;rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
     *          xmlns:dcterms="http://purl.org/dc/terms/"
     *         xmlns:openskos="http://openskos.org/xmlns#">
     *   &lt;rdf:Description rdf:about="http://mertens/knaw/dataset_5980699b-2c9a-4717-ac30-aed13743cc84/conceptscheme_fed05e8d-f586-45b5-934a-7e8fccb61871">
     *    &lt;rdf:type rdf:resource="http://www.w3.org/2004/02/skos/core#ConceptScheme"/>
     *    &lt;dcterms:dateSubmitted rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2017-02-21T17:52:54+00:00&lt;/dcterms:dateSubmitted>
     *    &lt;dcterms:creator rdf:resource="http://localhost/clavas/public/api/users/26272b05-6833-4ace-8e36-5b650fcefcdb"/>
     *    &lt;openskos:set rdf:resource="http://mertens/knaw/dataset_5980699b-2c9a-4717-ac30-aed13743cc84"/>
     *    &lt;dcterms:description xml:lang="nl">example1&lt;/dcterms:description>
     *    &lt;dcterms:title xml:lang="nl">Schema 1&lt;/dcterms:title>
     *    &lt;openskos:uuid>fed05e8d-f586-45b5-934a-7e8fccb61871&lt;/openskos:uuid>
     *    &lt;openskos:tenant rdf:resource="http://mertens/knaw/formalorganization_10302a0e-7e4e-4dbb-bce0-59e2a21c8785"/>
     *  &lt;/rdf:Description>
     * &lt;/rdf:RDF>
     * 
     * @apiError NotFound X-Error-Msg: The requested resource &lt;id> of type http://www.w3.org/2004/02/skos/core#ConceptScheme was not found in the triple store.
     * @apiErrorExample Not found:
     *   HTTP/1.1 404 Not Found
     *   The requested resource &lt;id> of type http://www.w3.org/2004/02/skos/core#ConceptScheme was not found in the triple store.
     */
    
    public function getAction()
    {
        parent::getAction();
    }
    
     /**
     *
     * @apiVersion 1.0.0
     * @apiDescription Create a new SKOS concept scheme based on the post data.
     * The concept schema's title provided in the requests' body has an obligatory attribute "language".
     * The title must be unique per language and single per language.
     * The reference to a set, to which  the schema under submission belongs to, must be the reference to an existing set.
     * If one of the conditions above is not fullfilled the validator will throw an error.
     *
     @apiExample {String} Example request
     * <rdf:RDF xmlns:rdf = "http://www.w3.org/1999/02/22-rdf-syntax-ns#"
     *          xmlns:openskos = "http://openskos.org/xmlns#"
     *          xmlns:dcterms = "http://purl.org/dc/terms/">
     *     <rdf:Description>
     *         <dcterms:title xml:lang="nl">Schema 1&lt;/dcterms:title>
     *         <dcterms:description xml:lang="nl">example1&lt;/dcterms:description>
     *        <openskos:set rdf:resource="http://mertens/knaw/dataset_5980699b-2c9a-4717-ac30-aed13743cc84"/>
     *    </rdf:Description>
     * </rdf:RDF>
     *
     * @api {post} /api/conceptscheme Create SKOS concept scheme
     * @apiName CreateConceptScheme
     * @apiGroup ConceptScheme
     *
     * @apiParam {String} tenant The institute code for your institute in the OpenSKOS portal
     * @apiParam {String} key A valid API key
     * @apiParam {String="true","false","1","0"} autoGenerateIdentifiers If set to true (any of "1", "true", "on" and "yes") the concept uri (rdf:about) and uuid will be automatically generated.
     *                                           If uri exists in the xml and autoGenerateIdentifiers is true - an error will be thrown.
     *                                           If set to false - the xml must contain uri (rdf:about) and uuid.
     * @apiSuccess (Created 201) {String} Location Concept scheme uri.
     * @apiSuccessExample {xml+rdf} Success-Response:
     *   HTTP/1.1 201 Created
     * &lt;rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
     *       xmlns:openskos="http://openskos.org/xmlns#"
     *       xmlns:dcterms="http://purl.org/dc/terms/">
     *  &lt;rdf:Description rdf:about="http://mertens/knaw/dataset_5980699b-2c9a-4717-ac30-aed13743cc84/conceptscheme_fed05e8d-f586-45b5-934a-7e8fccb61871">
     *     &lt;rdf:type rdf:resource="http://www.w3.org/2004/02/skos/core#ConceptScheme"/>
     *     &lt;dcterms:dateSubmitted rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2017-02-21T17:52:54+00:00&lt;/dcterms:dateSubmitted>
     *     &lt;dcterms:creator rdf:resource="http://localhost/clavas/public/api/users/26272b05-6833-4ace-8e36-5b650fcefcdb"/>
     *     &lt;openskos:set rdf:resource="http://mertens/knaw/dataset_5980699b-2c9a-4717-ac30-aed13743cc84"/>
     *     &lt;dcterms:description xml:lang="nl">example1&lt;/dcterms:description>
     *     &lt;dcterms:title xml:lang="nl">Schema 1&lt;/dcterms:title>
     *     &lt;openskos:uuid>fed05e8d-f586-45b5-934a-7e8fccb61871&lt;/openskos:uuid>
     *   &lt;/rdf:Description>
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
     * @apiError ConceptSchemeExists X-Error-Msg: The resource with &lt;id> already exists. Use PUT instead.
     * @apiErrorExample ConceptSchemeExists:
     *   HTTP/1.1 400 Bad request
     *   The resource with &lt;id> already exists. Use PUT instead.
     * 
     * @apiError ValidationError X-Error-Msg: The resource (of type http://www.w3.org/ns/org#Dataset) referred by  uri &lt;sets's reference> is not found.
     * @apiErrorExample ValidationError: 
     *   HTTP/1.1 400 Bad request
     *   The resource (of type http://www.w3.org/ns/org#Dataset) referred by  uri &lt;sets's reference> is not found.
     * 
     * @apiError ValidationError X-Error-Msg: The resource with the property http://purl.org/dc/terms/title set to &lt;dctermstitle> has been already registered.
     * @apiErrorExample ValidationError: 
     *   HTTP/1.1 400 Bad request
     *   The resource with the property http://purl.org/dc/terms/title set to &lt;dctermstitle> has been already registered.
     *
     * @apiError ValidationError X-Error-Msg: Title &lt;dctermstitle> is given without language.
     * @apiErrorExample ValidationError: 
     *   HTTP/1.1 400 Bad request
     *   Title &lt;dctermstitle>  is given without language.
     *
     * 
     */
    public function postAction()
    {
       parent::postAction();
    }
    
    /**
     *
     * @apiVersion 1.0.0
     * @apiDescription Update a SKOS concept scheme 
     *
     * Update a SKOS concept scheme based on the post data. The validation requrements are the same as for
     * the POST request.
     *
     * @apiExample {String} Example request
     * <rdf:RDF xmlns:rdf = "http://www.w3.org/1999/02/22-rdf-syntax-ns#"
     *          xmlns:openskos = "http://openskos.org/xmlns#"
     *          xmlns:dcterms = "http://purl.org/dc/terms/">
     *  <rdf:Description rdf:about="http://mertens/knaw/dataset_5980699b-2c9a-4717-ac30-aed13743cc84/conceptscheme_fed05e8d-f586-45b5-934a-7e8fccb61871">
     *     <openskos:set rdf:resource="http://mertens/knaw/dataset_5980699b-2c9a-4717-ac30-aed13743cc84"/>
     *     <dcterms:description xml:lang="nl">example1&lt;/dcterms:description>
     *     <dcterms:title xml:lang="nl">Schema 1 new&lt;/dcterms:title>
     *     <openskos:uuid>fed05e8d-f586-45b5-934a-7e8fccb61871&lt;/openskos:uuid>
     *   <rdf:Description>
     * <rdf:RDF>
     *
     * @api {put} /api/conceptscheme Update SKOS concept scheme
     * @apiName UpdateConceptScheme
     * @apiGroup ConceptScheme
     *
     * @apiParam {String} tenant The institute code for your institute in the OpenSKOS portal
     * @apiParam {String} key A valid API key
     * @apiParam {String="true","false","1","0"} autoGenerateIdentifiers If set to true (any of "1", "true", "on" and "yes") the concept uri (rdf:about) will be automatically generated.
     *                                           If uri exists in the xml and autoGenerateIdentifiers is true - an error will be thrown.
     *                                           If set to false - the xml must contain uri (rdf:about).
     * @apiSuccess {String} StatusCode 200 OK. 
     * @apiSuccessExample {xml+rdf} Success-Response:
     *   HTTP/1.1 200 OK
     * &lt;?xml version="1.0" encoding="utf-8" ?>
     * &lt;rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
     *       xmlns:openskos="http://openskos.org/xmlns#"
     *       xmlns:dcterms="http://purl.org/dc/terms/">
     *  &lt;rdf:Description rdf:about="http://mertens/knaw/dataset_5980699b-2c9a-4717-ac30-aed13743cc84/conceptscheme_fed05e8d-f586-45b5-934a-7e8fccb61871">
     *     &lt;rdf:type rdf:resource="http://www.w3.org/2004/02/skos/core#ConceptScheme"/>
     *     &lt;dcterms:dateSubmitted rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2017-02-21T17:52:54+00:00&lt;/dcterms:dateSubmitted>
     *     &lt;dcterms:creator rdf:resource="http://localhost/clavas/public/api/users/26272b05-6833-4ace-8e36-5b650fcefcdb"/>
     *     &lt;openskos:set rdf:resource="http://mertens/knaw/dataset_5980699b-2c9a-4717-ac30-aed13743cc84"/>
     *     &lt;dcterms:description xml:lang="nl">example1&lt;/dcterms:description>
     *     &lt;dcterms:title xml:lang="nl">Schema 1 new&lt;/dcterms:title>
     *     &lt;openskos:uuid>fed05e8d-f586-45b5-934a-7e8fccb61871&lt;/openskos:uuid>
     *   &lt;/rdf:Description>
     * &lt;/rdf:RDF>
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
     * 
     * Validation errors are identic to validation errors for POST requests.
     */
    public function putAction()
    {
        parent::putAction();
    }
    
    /**
     *
     * @apiVersion 1.0.0
     * @apiDescription Delete a SKOS concept scheme 
    
     * Delete a SKOS scheme by its uri
     * @api {delete} /api/conceptscheme delete SKOS concept scheme
     * @apiName DeleteConceptScheme
     * @apiGroup ConceptScheme
     *
     * @apiParam {String} tenant The institute code for your institute in the OpenSKOS portal
     * @apiParam {String} key A valid API key
       @apiSuccess {String} OK
     * @apiSuccessExample {xml+rdf} Success-Response
     *   HTTP/1.1 200 OK
     * &lt;?xml version="1.0" encoding="utf-8" ?>
     * &lt;rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
     *       xmlns:openskos="http://openskos.org/xmlns#"
     *       xmlns:dcterms="http://purl.org/dc/terms/">
     *  &lt;rdf:Description rdf:about="http://mertens/knaw/dataset_5980699b-2c9a-4717-ac30-aed13743cc84/conceptscheme_fed05e8d-f586-45b5-934a-7e8fccb61871">
     *     &lt;rdf:type rdf:resource="http://www.w3.org/2004/02/skos/core#ConceptScheme"/>
     *     &lt;dcterms:dateSubmitted rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2017-02-21T17:52:54+00:00&lt;/dcterms:dateSubmitted>
     *     &lt;dcterms:creator rdf:resource="http://localhost/clavas/public/api/users/26272b05-6833-4ace-8e36-5b650fcefcdb"/>
     *     &lt;openskos:set rdf:resource="http://mertens/knaw/dataset_5980699b-2c9a-4717-ac30-aed13743cc84"/>
     *     &lt;dcterms:description xml:lang="nl">example1&lt;/dcterms:description>
     *     &lt;dcterms:title xml:lang="nl">Schema 1 new&lt;/dcterms:title>
     *     &lt;openskos:uuid>fed05e8d-f586-45b5-934a-7e8fccb61871&lt;/openskos:uuid>
     *   &lt;/rdf:Description>
     * &lt;/rdf:RDF>
     * 
     * @apiError Not found X-Error-Msg: The requested resource &lt;uri> of type http://www.w3.org/2004/02/skos/core#ConceptScheme was not found in the triple store.
     * @apiErrorExample NotFound
     *   HTTP/1.1 404 NotFound
     *   The requested resource &lt;uri> of type http://www.w3.org/2004/02/skos/core#ConceptScheme was not found in the triple store.
     *  
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
    public function deleteAction()
    {
        parent::deleteAction();
    }
}
