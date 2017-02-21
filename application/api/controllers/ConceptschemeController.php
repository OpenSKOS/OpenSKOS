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
    
     public function indexAction()
    {
       parent::indexAction();
    }
   
    public function getAction()
    {
        parent::getAction();
    }
    
     /**
     *
     * @apiVersion 1.0.0
     * @apiDescription Create a SKOS concept scheme 
    
     * Create a new SKOS scheme based on the post data
     *
     @apiExample {String} Example request
     * <rdf:RDF xmlns:rdf = "http://www.w3.org/1999/02/22-rdf-syntax-ns#"
     * xmlns:openskos = "http://openskos.org/xmlns#"
     * xmlns:dcterms = "http://purl.org/dc/terms/">
     *     <rdf:Description>
     *         <dcterms:title xml:lang="nl">Schema 1</dcterms:title>
     *         <dcterms:description xml:lang="nl">example1</dcterms:description>
     *        <openskos:set rdf:resource="http://mertens/knaw/dataset_5980699b-2c9a-4717-ac30-aed13743cc84"/>
     *    </rdf:Description>
     * </rdf:RDF>
     *
     * @api {post} /api/conceptscheme Create SKOS concept scheme
     * @apiName CreateConceptScheme
     * @apiGroup Concept Scheme
     *
     * @apiParam {String} tenant The institute code for your institute in the OpenSKOS portal
     * @apiParam {String} key A valid API key
     * @apiParam {String="true","false","1","0"} autoGenerateIdentifiers If set to true (any of "1", "true", "on" and "yes") the concept uri (rdf:about) will be automatically generated.
     *                                           If uri exists in the xml and autoGenerateIdentifiers is true - an error will be thrown.
     *                                           If set to false - the xml must contain uri (rdf:about).
     * @apiSuccess (201) {String} ConceptScheme uri
     * @apiSuccessExample {String} Success-Response
     *   HTTP/1.1 201 Created
     * <?xml version="1.0" encoding="utf-8" ?>
     * <rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
     *       xmlns:openskos="http://openskos.org/xmlns#"
     *       xmlns:dcterms="http://purl.org/dc/terms/">
     *  <rdf:Description rdf:about="http://mertens/knaw/dataset_5980699b-2c9a-4717-ac30-aed13743cc84/conceptscheme_fed05e8d-f586-45b5-934a-7e8fccb61871">
     *     <rdf:type rdf:resource="http://www.w3.org/2004/02/skos/core#ConceptScheme"/>
     *     <dcterms:dateSubmitted rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2017-02-21T17:52:54+00:00</dcterms:dateSubmitted>
     *     <dcterms:creator rdf:resource="http://localhost/clavas/public/api/users/26272b05-6833-4ace-8e36-5b650fcefcdb"/>
     *     <openskos:set rdf:resource="http://mertens/knaw/dataset_5980699b-2c9a-4717-ac30-aed13743cc84"/>
     *     <dcterms:description xml:lang="nl">example1</dcterms:description>
     *     <dcterms:title xml:lang="nl">Schema 1</dcterms:title>
     *     <openskos:uuid>fed05e8d-f586-45b5-934a-7e8fccb61871</openskos:uuid>
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
     * @apiError ConceptSchemeExists {String} X-Error-Msg: The resource with <id> already exists. Use PUT instead.
     * @apiErrorExample ConceptSchemeExists:
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
     * @apiDescription Update a SKOS concept scheme 
    
     * Update a SKOS scheme based on the post data
     *
     @apiExample {String} Example request
     * <rdf:RDF xmlns:rdf = "http://www.w3.org/1999/02/22-rdf-syntax-ns#"
     * xmlns:openskos = "http://openskos.org/xmlns#"
     * xmlns:dcterms = "http://purl.org/dc/terms/">
     *  <rdf:Description rdf:about="http://mertens/knaw/dataset_5980699b-2c9a-4717-ac30-aed13743cc84/conceptscheme_fed05e8d-f586-45b5-934a-7e8fccb61871">
     *     <openskos:set rdf:resource="http://mertens/knaw/dataset_5980699b-2c9a-4717-ac30-aed13743cc84"/>
     *     <dcterms:description xml:lang="nl">example1</dcterms:description>
     *     <dcterms:title xml:lang="nl">Schema 1 new</dcterms:title>
     *     <openskos:uuid>fed05e8d-f586-45b5-934a-7e8fccb61871</openskos:uuid>
     *   </rdf:Description>
     * </rdf:RDF>
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
     * @apiSuccess (200) 
     * @apiSuccessExample {String} Success-Response
     *   HTTP/1.1 200 OK
     * <?xml version="1.0" encoding="utf-8" ?>
     * <rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
     *       xmlns:openskos="http://openskos.org/xmlns#"
     *       xmlns:dcterms="http://purl.org/dc/terms/">
     *  <rdf:Description rdf:about="http://mertens/knaw/dataset_5980699b-2c9a-4717-ac30-aed13743cc84/conceptscheme_fed05e8d-f586-45b5-934a-7e8fccb61871">
     *     <rdf:type rdf:resource="http://www.w3.org/2004/02/skos/core#ConceptScheme"/>
     *     <dcterms:dateSubmitted rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2017-02-21T17:52:54+00:00</dcterms:dateSubmitted>
     *     <dcterms:creator rdf:resource="http://localhost/clavas/public/api/users/26272b05-6833-4ace-8e36-5b650fcefcdb"/>
     *     <openskos:set rdf:resource="http://mertens/knaw/dataset_5980699b-2c9a-4717-ac30-aed13743cc84"/>
     *     <dcterms:description xml:lang="nl">example1</dcterms:description>
     *     <dcterms:title xml:lang="nl">Schema 1 new</dcterms:title>
     *     <openskos:uuid>fed05e8d-f586-45b5-934a-7e8fccb61871</openskos:uuid>
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
     */
    public function putAction()
    {
        parent::putAction();
    }
    
    /**
     *
     * @apiVersion 1.0.0
     * @apiDescription Delete a SKOS concept scheme 
    
     * Delete a SKOS scheme by it uri
     * @api {delete} /api/conceptscheme delete SKOS concept scheme
     * @apiName DeleteConceptScheme
     * @apiGroup ConceptScheme
     *
     * @apiParam {String} tenant The institute code for your institute in the OpenSKOS portal
     * @apiParam {String} key A valid API key
       @apiSuccess (2002) 
     * @apiSuccessExample {String} Success-Response
     *   HTTP/1.1 202 Accepted
     * <?xml version="1.0" encoding="utf-8" ?>
     * <rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
     *       xmlns:openskos="http://openskos.org/xmlns#"
     *       xmlns:dcterms="http://purl.org/dc/terms/">
     *  <rdf:Description rdf:about="http://mertens/knaw/dataset_5980699b-2c9a-4717-ac30-aed13743cc84/conceptscheme_fed05e8d-f586-45b5-934a-7e8fccb61871">
     *     <rdf:type rdf:resource="http://www.w3.org/2004/02/skos/core#ConceptScheme"/>
     *     <dcterms:dateSubmitted rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2017-02-21T17:52:54+00:00</dcterms:dateSubmitted>
     *     <dcterms:creator rdf:resource="http://localhost/clavas/public/api/users/26272b05-6833-4ace-8e36-5b650fcefcdb"/>
     *     <openskos:set rdf:resource="http://mertens/knaw/dataset_5980699b-2c9a-4717-ac30-aed13743cc84"/>
     *     <dcterms:description xml:lang="nl">example1</dcterms:description>
     *     <dcterms:title xml:lang="nl">Schema 1 new</dcterms:title>
     *     <openskos:uuid>fed05e8d-f586-45b5-934a-7e8fccb61871</openskos:uuid>
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
     */
    public function deleteAction()
    {
        parent::deleteAction();
    }
}
