<?php

require_once 'AbstractController.php';

class Api_SkoscollectionController extends AbstractController
{
     public function init()
    {
       parent::init();
       $this->fullNameResourceClass = 'OpenSkos2\Api\SkosCollection';
       $this ->viewpath="skoscollection/";
      
    }
    
    /**
     *
     * @apiVersion 1.0.0
     * @apiDescription Return a list of SKOS Collections
     * @api {get} /api/skoscollection[?format=rdf, ?format=html, ?format=json, ?format=jsonp&callback=f]   Get SKOS collections
     * @apiName GetSkosCollection
     * @apiGroup SkosCollection
     *
     * @apiParam {String=empty, "rdf","html","json","jsonp"}  format If set to jsonp, must contain parameter callback as well
     * @apiParam {String} callback If format set to jsonp, must be non-empty
     * 
     * @apiSuccess (200) OK
     * @apiSuccessExample {String} Success-Response
     *   HTTP/1.1 200 OK 
     * <?xml version="1.0"?>
     * <rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" 
     *  xmlns:dc="http://purl.org/dc/elements/1.1/" 
     *  xmlns:dcterms="http://purl.org/dc/terms/" 
     *  xmlns:skos="http://www.w3.org/2004/02/skos/core#" 
     *  xmlns:openskos="http://openskos.org/xmlns#" 
     *  openskos:numFound="2" openskos:rows="5000" 
     *  openskos:start="1">
     * <rdf:Description rdf:about="http://mertens/knaw/dataset_3cdae699-61f3-4454-b032-ccc6c4b2e5da/collection_2dfa20f7-9e84-4b80-9a00-83e5a66b6933">
     *   <rdf:type rdf:resource="http://www.w3.org/2004/02/skos/core#Collection"/>
     *   <dcterms:contributor rdf:resource="http://localhost/clavas/public/api/users/26272b05-6833-4ace-8e36-5b650fcefcdb"/>
     *   <dcterms:modified rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2017-02-22T13:05:47+00:00</dcterms:modified>
     *   <dcterms:dateSubmitted rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2017-02-22T12:46:23+00:00</dcterms:dateSubmitted>
     *   <dcterms:creator rdf:resource="http://localhost/clavas/public/api/users/26272b05-6833-4ace-8e36-5b650fcefcdb"/>
     *   <openskos:set rdf:resource="http://mertens/knaw/dataset_3cdae699-61f3-4454-b032-ccc6c4b2e5da"/>
     *   <dcterms:title xml:lang="en">SkosCollection1new</dcterms:title>
     *   <openskos:uuid>2dfa20f7-9e84-4b80-9a00-83e5a66b6933</openskos:uuid>
     *   <openskos:tenant rdf:resource="http://mertens/knaw/formalorganization_10302a0e-7e4e-4dbb-bce0-59e2a21c8785"/>
     * </rdf:Description>
     * <rdf:Description rdf:about="http://mertens/knaw/dataset_3cdae699-61f3-4454-b032-ccc6c4b2e5da/collection_5234162d-dc05-462f-90a6-5e42f4d5c07e">
     *   <rdf:type rdf:resource="http://www.w3.org/2004/02/skos/core#Collection"/>
     *   <dcterms:dateSubmitted rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2017-02-22T12:46:23+00:00</dcterms:dateSubmitted>
     *   <dcterms:creator rdf:resource="http://localhost/clavas/public/api/users/26272b05-6833-4ace-8e36-5b650fcefcdb"/>
     *   <openskos:set rdf:resource="http://mertens/knaw/dataset_3cdae699-61f3-4454-b032-ccc6c4b2e5da"/>
     *   <dcterms:title xml:lang="en">SkosCollection1</dcterms:title>
     *   <openskos:uuid>5234162d-dc05-462f-90a6-5e42f4d5c07e</openskos:uuid>
     *   <openskos:tenant rdf:resource="http://mertens/knaw/formalorganization_10302a0e-7e4e-4dbb-bce0-59e2a21c8785"/>
     * </rdf:Description>
     * </rdf:RDF>
    * 
    */
     public function indexAction()
    {
       parent::indexAction();
    }
   
    /**
     *
     * @apiVersion 1.0.0
     * @apiDescription Return a specific SKOS collection by its uri or uuid
     *
     * @api {get} /api/skoscollection/{uuid}[.rdf, .html, .json, .jsonp] Get SKOS collection details
     * @apiName GetSkosCollection
     * @apiGroup SkosCollection
     *
     * @apiParam {String} id (uuid, or uri)
     * @apiParam {String=empty, "rdf","html","json","jsonp"}  format If set to jsonp, must contain parameter callback as well
     * @apiParam {String} callback If format set to jsonp, must be non-empty
     * 
     * @apiSuccess (200) OK
     * @apiSuccessExample {String} Success-Response
     *   HTTP/1.1 200 OK
     * <?xml version="1.0" encoding="utf-8" ?>
     * <?xml version="1.0"?>
     * <rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" 
     *  xmlns:dc="http://purl.org/dc/elements/1.1/" 
     *  xmlns:dcterms="http://purl.org/dc/terms/" 
     *  xmlns:skos="http://www.w3.org/2004/02/skos/core#" 
     *  xmlns:openskos="http://openskos.org/xmlns#" 
     *  openskos:numFound="1" openskos:rows="5000" openskos:start="1">
     * <rdf:Description rdf:about="http://mertens/knaw/dataset_3cdae699-61f3-4454-b032-ccc6c4b2e5da/collection_5234162d-dc05-462f-90a6-5e42f4d5c07e">
     *    <rdf:type rdf:resource="http://www.w3.org/2004/02/skos/core#Collection"/>
     *     <dcterms:dateSubmitted rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2017-02-22T12:46:23+00:00</dcterms:dateSubmitted>
     *     <dcterms:creator rdf:resource="http://localhost/clavas/public/api/users/26272b05-6833-4ace-8e36-5b650fcefcdb"/>
     *     <openskos:set rdf:resource="http://mertens/knaw/dataset_3cdae699-61f3-4454-b032-ccc6c4b2e5da"/>
     *     <dcterms:title xml:lang="en">SkosCollection1</dcterms:title>
     *     <openskos:uuid>5234162d-dc05-462f-90a6-5e42f4d5c07e</openskos:uuid>
     *     <openskos:tenant rdf:resource="http://mertens/knaw/formalorganization_10302a0e-7e4e-4dbb-bce0-59e2a21c8785"/>
     *   </rdf:Description>
     * </rdf:RDF>
     * @apiError NotFound {String} The requested resource <id> of type http://www.w3.org/2004/02/skos/core#Collection was not found in the triple store.
     * @apiErrorExample Not found:
     *   HTTP/1.1 404 Not Found
     *   The requested resource <id> of type http://www.w3.org/2004/02/skos/core#Collection was not found in the triple store.
     */
    public function getAction()
    {
        parent::getAction();
    }
    
    /**
     *
     * @apiVersion 1.0.0
     * @apiDescription Create a SKOS collecion 
    
     * Create a new SKOS collection based on the post data
     *
     @apiExample {String} Example request
     * <rdf:RDF xmlns:rdf = "http://www.w3.org/1999/02/22-rdf-syntax-ns#"
     * xmlns:openskos = "http://openskos.org/xmlns#"
     * xmlns:dcterms = "http://purl.org/dc/terms/"
     * xmlns:skos="http://www.w3.org/2004/02/skos/core#">
     *     <rdf:Description>
     *     <openskos:set rdf:resource="http://mertens/knaw/dataset_3cdae699-61f3-4454-b032-ccc6c4b2e5da"/>
     *     <dcterms:title xml:lang="en">SkosCollection1</dcterms:title>
     *</rdf:Description>
     * </rdf:RDF>
     *
     * @api {post} /api/skoscollection Create SKOS collection
     * @apiName CreateSKosCollection
     * @apiGroup SkosCollection
     *
     * @apiParam {String} tenant The institute code for your institute in the OpenSKOS portal
     * @apiParam {String} key A valid API key
     * @apiParam {String="true","false","1","0"} autoGenerateIdentifiers If set to true (any of "1", "true", "on" and "yes") the concept uri (rdf:about) will be automatically generated.
     *                                           If uri exists in the xml and autoGenerateIdentifiers is true - an error will be thrown.
     *                                           If set to false - the xml must contain uri (rdf:about).
     * @apiSuccess (201) {String} SkosCollection uri
     * @apiSuccessExample {String} Success-Response
     *   HTTP/1.1 201 Created
     * <?xml version="1.0" encoding="utf-8" ?>
     * <?xml version="1.0" encoding="utf-8" ?>
     * <rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
     *         xmlns:dcterms="http://purl.org/dc/terms/"
     *          xmlns:openskos="http://openskos.org/xmlns#">
     *  <rdf:Description rdf:about="http://mertens/knaw/dataset_3cdae699-61f3-4454-b032-ccc6c4b2e5da/collection_5234162d-dc05-462f-90a6-5e42f4d5c07e">
     *    <rdf:type rdf:resource="http://www.w3.org/2004/02/skos/core#Collection"/>
     *    <dcterms:dateSubmitted rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2017-02-22T12:46:23+00:00</dcterms:dateSubmitted>
     *     <dcterms:creator rdf:resource="http://localhost/clavas/public/api/users/26272b05-6833-4ace-8e36-5b650fcefcdb"/>
     *    <openskos:set rdf:resource="http://mertens/knaw/dataset_3cdae699-61f3-4454-b032-ccc6c4b2e5da"/>
     *    <dcterms:title xml:lang="en">SkosCollection1</dcterms:title>
     *    <openskos:uuid>5234162d-dc05-462f-90a6-5e42f4d5c07e</openskos:uuid>
     *   </rdf:Description>
     * </rdf:RDF>
     * @apiError MissingKey {String} No key specified
     * @apiErrorExample MissingKey:
     *   HTTP/1.1 412 Precondition Failed
     *   No key specified
     * @apiError MissingTenant {String} No tenant specified
     * @apiErrorExample MissingTenant:
     *   HTTP/1.1 412 Precondition Failed
     *   No tenant specified
     * @apiError SkosCollectionExists {String} X-Error-Msg: The resource with <id> already exists. Use PUT instead.
     * @apiErrorExample SkosCollectionExists:
     *   HTTP/1.1 400 Bad request
     *   The resource with <id> already exists. Use PUT instead.
     */
    public function postAction()
    {
       parent::postAction();
    }
    
    /**
     *
     * @apiVersion 1.0.0
     * @apiDescription Update a SKOS collecion 
    
     * Update a SKOS collection based on the post data
     *
     * @apiExample {String} Example request
     * <rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
     *          xmlns:dcterms="http://purl.org/dc/terms/"
     *         xmlns:openskos="http://openskos.org/xmlns#"
     *          xmlns:skos="http://www.w3.org/2004/02/skos/core#">
     *  <rdf:Description rdf:about="http://mertens/knaw/dataset_3cdae699-61f3-4454-b032-ccc6c4b2e5da/collection_2dfa20f7-9e84-4b80-9a00-83e5a66b6933">
     *    <openskos:set rdf:resource="http://mertens/knaw/dataset_3cdae699-61f3-4454-b032-ccc6c4b2e5da"/>
     *    <dcterms:title xml:lang="en">SkosCollection1new</dcterms:title>
     *    <openskos:uuid>2dfa20f7-9e84-4b80-9a00-83e5a66b6933</openskos:uuid>
     * </rdf:RDF>
     *
     * @api {post} /api/skoscollection Update SKOS collection
     * @apiName UpdateSkosCollection
     * @apiGroup SkosCollection
     *
     * @apiParam {String} tenant The institute code for your institute in the OpenSKOS portal
     * @apiParam {String} key A valid API key
     * @apiParam {String="true","false","1","0"} autoGenerateIdentifiers If set to true (any of "1", "true", "on" and "yes") the concept uri (rdf:about) will be automatically generated.
     *                                           If uri exists in the xml and autoGenerateIdentifiers is true - an error will be thrown.
     *                                           If set to false - the xml must contain uri (rdf:about).
     * @apiSuccess (200) {String} 
     * @apiSuccessExample {String} Success-Response
     *   HTTP/1.1 201 Created
     * <?xml version="1.0" encoding="utf-8" ?>
     * <rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
     *         xmlns:dcterms="http://purl.org/dc/terms/"
     *          xmlns:openskos="http://openskos.org/xmlns#">
     *  <rdf:Description rdf:about="http://mertens/knaw/dataset_3cdae699-61f3-4454-b032-ccc6c4b2e5da/collection_5234162d-dc05-462f-90a6-5e42f4d5c07e">
     *     <rdf:type rdf:resource="http://www.w3.org/2004/02/skos/core#Collection"/>
     *      <dcterms:contributor rdf:resource="http://localhost/clavas/public/api/users/26272b05-6833-4ace-8e36-5b650fcefcdb"/>
     *      <dcterms:modified rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2017-02-22T13:05:47+00:00</dcterms:modified>
     *      <dcterms:dateSubmitted rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2017-02-22T12:46:23+00:00</dcterms:dateSubmitted>
     *      <dcterms:creator rdf:resource="http://localhost/clavas/public/api/users/26272b05-6833-4ace-8e36-5b650fcefcdb"/>
     *      <openskos:set rdf:resource="http://mertens/knaw/dataset_3cdae699-61f3-4454-b032-ccc6c4b2e5da"/>
     *      <dcterms:title xml:lang="en">SkosCollection1new</dcterms:title>
     *      <openskos:uuid>5234162d-dc05-462f-90a6-5e42f4d5c07e</openskos:uuid>
     *   </rdf:Description>
     * </rdf:RDF>
     * @apiError MissingKey {String} X-Error-Msg: No key specified
     * @apiErrorExample MissingKey:
     *   HTTP/1.1 412 Precondition Failed
     *   No key specified
     * @apiError MissingTenant {String} X-Error-Msg: No tenant specified
     * @apiErrorExample MissingTenant:
     *   HTTP/1.1 412 Precondition Failed
     *   No tenant specified
     * @apiError MissedUri {String} X-Error-Msg:  Missed uri (rdf:about)!
     * @apiErrorExample MissedUri: 
     *   HTTP/1.1 400 Bad request
     *   Missed uri (rdf:about)!
     * @apiError ChangedOrNoUuid {String} X-Error-Msg:  You cannot change UUID of the resouce. Keep it <oldUuid>
     * @apiErrorExample ChangedOrNoUuid: 
     *   HTTP/1.1 400 Bad request
     *   You cannot change UUID of the resouce. Keep it <oldUuid>
     */
    public function putAction()
    {
        parent::putAction();
    }
    
    /**
     *
     * @apiVersion 1.0.0
     * @apiDescription Delete a SKOS collection by its uri
     * @api {delete} /api/skoscollection delete SKOS collection
     * @apiName DeleteSkosCollection
     * @apiGroup SkosCollection
     *
     * @apiParam {String} tenant The institute code for your institute in the OpenSKOS portal
     * @apiParam {String} key A valid API key
     * @apiParam {String} uri The uri of the skoscollection
     * @apiSuccess (2002) 
     * @apiSuccessExample {String} Success-Response
     *   HTTP/1.1 202 Accepted
     * <?xml version="1.0" encoding="utf-8" ?>
     * <rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
     *       xmlns:openskos="http://openskos.org/xmlns#"
     *       xmlns:dcterms="http://purl.org/dc/terms/">
     *  <rdf:Description rdf:about="http://mertens/knaw/dataset_3cdae699-61f3-4454-b032-ccc6c4b2e5da/collection_2dfa20f7-9e84-4b80-9a00-83e5a66b6933">
     *     <rdf:type rdf:resource="http://www.w3.org/2004/02/skos/core#Collection"/>
     *     <dcterms:contributor rdf:resource="http://localhost/clavas/public/api/users/26272b05-6833-4ace-8e36-5b650fcefcdb"/>
     *      <dcterms:modified rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2017-02-22T13:05:47+00:00</dcterms:modified>
     *     <dcterms:dateSubmitted rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2017-02-22T12:46:23+00:00</dcterms:dateSubmitted>
     *     <dcterms:creator rdf:resource="http://localhost/clavas/public/api/users/26272b05-6833-4ace-8e36-5b650fcefcdb"/>
     *     <openskos:set rdf:resource="http://mertens/knaw/dataset_3cdae699-61f3-4454-b032-ccc6c4b2e5da"/>
     *     <dcterms:title xml:lang="en">SkosCollection1new</dcterms:title>
     *     <openskos:uuid>2dfa20f7-9e84-4b80-9a00-83e5a66b6933</openskos:uuid>
     * </rdf:RDF>
     * @apiError Not found {String} The requested resource <uri> of type type http://www.w3.org/2004/02/skos/core#Collection was not found in the triple store.
     * @apiErrorExample NotFound
     *   HTTP/1.1 404 NotFound
     *   The requested resource <uri> of type type http://www.w3.org/2004/02/skos/core#Collection was not found in the triple store.
     * @apiError MissingKey {String} No key specified
     * @apiErrorExample MissingKey:
     *   HTTP/1.1 412 Precondition Failed
     *   No key specified
     * @apiError MissingTenant {String} No tenant specified
     * @apiErrorExample MissingTenant:
     *   HTTP/1.1 412 Precondition Failed
     *   No tenant specified
     * @apiError MissedUri {String} X-Error-Msg:  Missing uri parameter
     * @apiErrorExample MissedUri: 
     *   HTTP/1.1 400 Bad request
     *   Missing uri parameter
     */
    public function deleteAction()
    {
        parent::deleteAction();
    }
}