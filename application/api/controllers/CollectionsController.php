<?php

require_once 'AbstractController.php';

class Api_CollectionsController extends AbstractController
{
   
    public function init()
    {
       parent::init();
       $this->apiResourceClass = 'OpenSkos2\Api\Set';
       $this ->viewpath="set/";
    }
    
    /**
     *
     * @apiVersion 1.0.0
     * @apiDescription Get a detailed list of OpenSKOS sets
     * 
     * in RDF: /api/collections/  or /api/collections?format=rdf
     * 
     * in JSON: /api/collections?format=json
     * 
     * in JSONP: /api/collections?format=jsonp&callback=myCallback1234
     * 
     * in HTML: /api/collections?format=html
     *
     * in JSON as name-uri map: /api/collections?shortlist=true&format=json
     *  
     * @api {get} /api/collections  Get OpenSKOS sets
     * @apiName GetSets
     * @apiGroup Set
     *
     * @apiParam {String=empty, "rdf","html","json","jsonp"}  format If set to jsonp, must contain parameter callback as well
     * @apiParam {String} callback If format set to jsonp, must be non-empty
     * @apiParam {String=empty, true, false, 1, 0} shortlist If set to true, then format must be set to json
     * 
     * 
     * @apiSuccess {xml/json/jsonp/html} Body
     * @apiSuccessExample Success-Response:
     *   HTTP/1.1 200 OK
     * &lt;rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" 
     *    xmlns:dc="http://purl.org/dc/elements/1.1/"
     *    xmlns:dcterms="http://purl.org/dc/terms/" 
     *    xmlns:skos="http://www.w3.org/2004/02/skos/core#" 
     *    xmlns:openskos="http://openskos.org/xmlns#" 
     *    openskos:numFound="2" openskos:rows="5000" 
     *    openskos:start="1">
     * &lt;rdf:Description rdf:about="http://mertens/knaw/dataset_3cdae699-61f3-4454-b032-ccc6c4b2e5da">
     *     &lt;rdf:type rdf:resource="http://purl.org/dc/dcmitype#Dataset"/>
     *     &lt;openskos:allow_oai rdf:datatype="http://www.w3.org/2001/XMLSchema#bool">true&lt;/openskos:allow_oai>
     *     &lt;openskos:webpage rdf:resource="http://ergens2"/>
     *     &lt;dcterms:publisher rdf:resource="http://mertens/knaw/formalorganization_10302a0e-7e4e-4dbb-bce0-59e2a21c8785"/>
     *     &lt;openskos:code>ISO-org&lt;/openskos:code>
     *     &lt;openskos:conceptBaseUri>http://example.com/set-example&lt;/openskos:conceptBaseUri>
     *     &lt;dcterms:license rdf:resource="http://creativecommons.org/licenses/by/4.0/"/>
     *     &lt;openskos:uuid>3cdae699-61f3-4454-b032-ccc6c4b2e5da&lt;/openskos:uuid>
     *     &lt;dcterms:title xml:lang="en">CLARIN Organisations&lt;/dcterms:title>
     *     &lt;openskos:OAI_baseURL rdf:resource="https://openskos.meertens.knaw.nl/api/ergens2"/>
     *   &lt;/rdf:Description>
     *   &lt;rdf:Description rdf:about="http://mertens/knaw/dataset_5980699b-2c9a-4717-ac30-aed13743cc84">
     *     &lt;rdf:type rdf:resource="http://purl.org/dc/dcmitype#Dataset"/>
     *     &lt;openskos:conceptBaseUri>http://example.com/collection-example&lt;/openskos:conceptBaseUri>
     *     &lt;openskos:code>ISO-lang&lt;/openskos:code>
     *     &lt;dcterms:title xml:lang="en">CLARIN Languages upd&lt;/dcterms:title>
     *     &lt;openskos:webpage rdf:resource="http://ergens"/>
     *     &lt;dcterms:license rdf:resource="http://creativecommons.org/licenses/by/4.0/"/>
     *     &lt;openskos:OAI_baseURL rdf:resource="https://openskos.meertens.knaw.nl/api/ergens1"/>
     *     &lt;openskos:allow_oai rdf:datatype="http://www.w3.org/2001/XMLSchema#bool">true&lt;/openskos:allow_oai>
     *     &lt;dcterms:publisher rdf:resource="http://mertens/knaw/formalorganization_10302a0e-7e4e-4dbb-bce0-59e2a21c8785"/>
     *     &lt;openskos:uuid>5980699b-2c9a-4717-ac30-aed13743cc84&lt;/openskos:uuid>
     *  &lt;/rdf:Description>
     *  &lt;/rdf:RDF>
     * 
     * @apiSuccessExample Success-Response for shortlist=true:
     * HTTP/1.1 200 OK
     * {
     *  "Clavas Laguages":"http://mertens/knaw/dataset_6c71d9c1-e4cc-4aa7-980c-cada7702e372",
     *  "Clavas Organisations":"http://mertens/knaw/dataset_96036967-5215-413a-a3bc-f4c07b14c16b",
     *  "clavas set 3":"http://hdl.handle.net/11148/backendname_dataset_3c30c1e5-9e55-44a2-9735-82a5d9a34336"
     * }
     */
     public function indexAction()
    {
      return parent::indexAction();
    }
    
    /**
     *
     * @apiVersion 1.0.0
     * @apiDescription Get an OpenSKOS Set details by its uri or uuid:
     * 
     * in RDF: /api/collections/{uuid} 
     * 
     * or /api/collections/{uuid.rdf}
     * 
     * or /api/collections?id={uuid}
     * 
     * or /api/collections?id={uuid}&format=rdf
     * 
     * or /api/collections?id={uri}
     * 
     * or /api/collections?id={uri}&format=rdf
     * 
     * in JSON: /api/collections/{uuid.json}
     * 
     * or /api/collections?id={uuid}&format=json
     * 
     * or /api/collections?id={uri}&format=json
     * 
     * in JSONP: /api/collections/{uuid.jsonp}?callback=myCallback1234
     * 
     * or /api/collections?id={uuid}&format=jsonp&callback=myCallback1234
     * 
     * or /api/collections?id={uri}&format=jsonp&callback=myCallback1234
     * 
     * in HTML: /api/collections/{uuid.html}
     * 
     * or /api/collections?id={uuid}&format=html
     * 
     * or /api/collections?id={uri}&format=html
     * 
     *
     * @api {get} /api/collections/{uuid} Get OpenSKOS set details
     * @apiName GetSet
     * @apiGroup Set
     *
     * @apiParam {String} id the set's uri or uuid
     * @apiParam {String=empty, "rdf","html","json","jsonp"}  format If set to jsonp, the request must contain a non-empty parameter "callback" as well
     * @apiParam {String} callback If format set to jsonp, must be non-empty
     * 
     * @apiSuccess {xml/json/jsonp/html} Body 
     * @apiSuccessExample Success-Response:
     *   HTTP/1.1 200 OK
     * &lt;?xml version="1.0" encoding="utf-8" ?>
     * &lt;rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
     *       xmlns:openskos="http://openskos.org/xmlns#"
     *       xmlns:dcterms="http://purl.org/dc/terms/">
     * &lt;rdf:Description rdf:about="http://mertens/knaw/dataset_5980699b-2c9a-4717-ac30-aed13743cc84">
     *   &lt;rdf:type rdf:resource="http://purl.org/dc/dcmitype#Dataset"/>
     *   &lt;openskos:conceptBaseUri>http://example.com/collection-example&lt;/openskos:conceptBaseUri>
     *   &lt;openskos:code>ISO-lang&lt;/openskos:code>
     *   &lt;openskos:webpage rdf:resource="http://ergens"/>
     *   &lt;dcterms:license rdf:resource="http://creativecommons.org/licenses/by/4.0/"/>
     *   &lt;openskos:OAI_baseURL rdf:resource="https://openskos.meertens.knaw.nl/api/ergens1"/>
     *   &lt;dcterms:title xml:lang="en">CLARIN Languages&lt;/dcterms:title>
     *   &lt;openskos:allow_oai rdf:datatype="http://www.w3.org/2001/XMLSchema#bool">true&lt;/openskos:allow_oai>
     *   &lt;dcterms:publisher rdf:resource="http://mertens/knaw/formalorganization_10302a0e-7e4e-4dbb-bce0-59e2a21c8785"/>
     *   &lt;openskos:uuid>5980699b-2c9a-4717-ac30-aed13743cc84&lt;/openskos:uuid>
     * &lt;/rdf:Description>
     * &lt;/rdf:RDF>
     * 
     * @apiError NotFound The requested resource &lt;id> of type http://purl.org/dc/dcmitype#Dataset was not found in the triple store.
     * @apiErrorExample Not found
     *   HTTP/1.1 404 Not Found
     *   The requested resource &lt;id> of type http://purl.org/dc/dcmitype#Dataset was not found in the triple store.
     */
   
    public function getAction()
    {
        return parent::getAction();
    }
    
    /**
     *
     * @apiVersion 1.0.0
     * @apiDescription Create a new OpenSKOS set (former collection) based on the post data.
     * The set's code and the web-page provided in the request body, must be unique. If publisher (which is the uri of 
     * the tenant) is not given, then the uri of the tenant set in the request parameters will be assigned to publisher 
     * property. 
     * To activate this API function, the parameter 'optional.authorisation' in application.ini must be set to the 
     * dicrectory where the authorisation procedure is implemented, for instance optional.authorisation = 
     * Custom\Authorisation. If this parameter is absent then 501 is thrown.
     *
     @apiExample {String} Example request
     * <?xml version="1.0" encoding="UTF-8"?>
     * <rdf:RDF xmlns:rdf = "http://www.w3.org/1999/02/22-rdf-syntax-ns#"
     *     xmlns:openskos = "http://openskos.org/xmlns#"
     *     xmlns:dcterms = "http://purl.org/dc/terms/"
     *     xmlns:dcmitype = "http://purl.org/dc/dcmitype#">
     *     <rdf:Description>
     *         <openskos:code>ISO-lang&lt;/openskos:code>
     *         <dcterms:title xml:lang="en">CLARIN Languages</dcterms:title>
     *         <dcterms:license rdf:resource="http://creativecommons.org/licenses/by/4.0/"></dcterms:license>
     *         <dcterms:publisher rdf:resource="http://mertens/knaw/formalorganization_10302a0e-7e4e-4dbb-bce0-59e2a21c8785"></dcterms:publisher>
     *         <openskos:OAI_baseURL rdf:resource="https://openskos.meertens.knaw.nl/api/ergens1"/>
     *         <openskos:allow_oai>true&lt;/openskos:allow_oai>
     *         <openskos:conceptBaseUri>http://example.com/collection-example&lt;/openskos:conceptBaseUri>
     *         <openskos:webpage rdf:resource="http://ergens"/>
     *      </rdf:Description>
     * </rdf:RDF>
     *
     * @api {post} /api/collections Create an OpenSKOS set
     * @apiName CreateSet
     * @apiGroup Set
     *
     * @apiParam {String} tenant The institute code for your institute in the OpenSKOS portal
     * @apiParam {String} key A valid API key
     * @apiParam {String="true","false","1","0"} autoGenerateIdentifiers If set to true (any of "1", "true", "on" and "yes") the set uri (rdf:about) and it uuid will be automatically generated.
     *                                           If uri and/or uuid exists in the xml and autoGenerateIdentifiers is true - an error will be thrown.
     *                                           If set to false - the xml must contain uri (rdf:about) and uuid.
     * @apiSuccess {String} Location Set uri
     * @apiSuccessExample Success-Response:
     *   HTTP/1.1 201 Created
     *   &lt;?xml version="1.0" encoding="utf-8" ?>
     *   &lt;rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
     *       xmlns:openskos="http://openskos.org/xmlns#"
     *       xmlns:dcterms="http://purl.org/dc/terms/">
     * &lt;rdf:Description rdf:about="http://mertens/knaw/dataset_5980699b-2c9a-4717-ac30-aed13743cc84">
     *   &lt;rdf:type rdf:resource="http://purl.org/dc/dcmitype#Dataset"/>
     *   &lt;openskos:conceptBaseUri>http://example.com/collection-example&lt;/openskos:conceptBaseUri>
     *   &lt;openskos:code>ISO-lang&lt;/openskos:code>
     *   &lt;openskos:webpage rdf:resource="http://ergens"/>
     *   &lt;dcterms:license rdf:resource="http://creativecommons.org/licenses/by/4.0/"/>
     *   &lt;openskos:OAI_baseURL rdf:resource="https://openskos.meertens.knaw.nl/api/ergens1"/>
     *   &lt;dcterms:title xml:lang="en">CLARIN Languages&lt;/dcterms:title>
     *   &lt;openskos:allow_oai rdf:datatype="http://www.w3.org/2001/XMLSchema#bool">true&lt;/openskos:allow_oai>
     *   &lt;dcterms:publisher rdf:resource="http://mertens/knaw/formalorganization_10302a0e-7e4e-4dbb-bce0-59e2a21c8785"/>
     *   &lt;openskos:uuid>5980699b-2c9a-4717-ac30-aed13743cc84&lt;/openskos:uuid>
     *   &lt;/rdf:Description>
     *  &lt;/rdf:RDF>
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
     * @apiError SetExists The resource with &lt;id> already exists. Use PUT instead.
     * @apiErrorExample SetExists:
     *   HTTP/1.1 400 Bad request
     *   The resource with &lt;id> already exists. Use PUT instead.
     * 
     * @apiError ValidationError The resource (of type http://www.w3.org/ns/org#FormalOrganization) referred by  uri &lt;publisher's reference> is not found.
     * @apiErrorExample ValidationError 
     *   HTTP/1.1 400 Bad request
     *   The resource (of type http://www.w3.org/ns/org#FormalOrganization) referred by  uri &lt;publisher's reference> is not found.
     *
     */
    public function postAction()
    {
      parent::postAction();
    }
    
        
    /**
     *
     * @apiVersion 1.0.0
     * @apiDescription Update an OpenSKOS set (former collection) based on the post data.
     * To activate this API function, the parameter 'optional.authorisation' in application.ini must be set to the 
     * dicrectory where the authorisation procedure is implemented, for instance optional.authorisation = 
     * Custom\Authorisation. If this parameter is absent then 501 is thrown.
     @apiExample {String} Example request
     * <?xml version="1.0" encoding="UTF-8"?>
     * <rdf:RDF xmlns:rdf = "http://www.w3.org/1999/02/22-rdf-syntax-ns#"
     *    xmlns:openskos = "http://openskos.org/xmlns#"
     *    xmlns:dcterms = "http://purl.org/dc/terms/"
     *   xmlns:dcmitype = "http://purl.org/dc/dcmitype#">
     *  <rdf:Description>
     *     <openskos:code>ISO-lang&lt;/openskos:code>
     *     <dcterms:title xml:lang="en">CLARIN Languages new</dcterms:title>
     *     <dcterms:license rdf:resource="http://creativecommons.org/licenses/by/4.0/"></dcterms:license>
     *     <dcterms:publisher rdf:resource="http://mertens/knaw/formalorganization_10302a0e-7e4e-4dbb-bce0-59e2a21c8785"></dcterms:publisher>
     *     <openskos:OAI_baseURL rdf:resource="https://openskos.meertens.knaw.nl/api/ergens1"/>
     *     <openskos:allow_oai>true&lt;/openskos:allow_oai>
     *     <openskos:conceptBaseUri>http://example.com/collection-example&lt;/openskos:conceptBaseUri>
     *     <openskos:webpage rdf:resource="http://ergens"/>
     *  </rdf:Description>
     * </rdf:RDF>
     *
     * @api {post} /api/collections Update SKOS set
     * @apiName UpdateSet
     * @apiGroup Set
     * @apiParam {String} tenant The institute code for your institute in the OpenSKOS portal
     * @apiParam {String} key A valid API key
       @apiSuccess {xml} Body
     * @apiSuccessExample Success-Response:
     *   HTTP/1.1 200 OK
     *   &lt;?xml version="1.0" encoding="utf-8" ?>
     *   &lt;rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
     *       xmlns:openskos="http://openskos.org/xmlns#"
     *       xmlns:dcterms="http://purl.org/dc/terms/">
     * &lt;rdf:Description rdf:about="http://mertens/knaw/dataset_5980699b-2c9a-4717-ac30-aed13743cc84">
     *   &lt;rdf:type rdf:resource="http://purl.org/dc/dcmitype#Dataset"/>
     *   &lt;openskos:conceptBaseUri>http://example.com/collection-example&lt;/openskos:conceptBaseUri>
     *   &lt;openskos:code>ISO-lang&lt;/openskos:code>
     *   &lt;openskos:webpage rdf:resource="http://ergens"/>
     *   &lt;dcterms:license rdf:resource="http://creativecommons.org/licenses/by/4.0/"/>
     *   &lt;openskos:OAI_baseURL rdf:resource="https://openskos.meertens.knaw.nl/api/ergens1"/>
     *   &lt;dcterms:title xml:lang="en">CLARIN Languages new&lt;/dcterms:title>
     *   &lt;openskos:allow_oai rdf:datatype="http://www.w3.org/2001/XMLSchema#bool">true&lt;/openskos:allow_oai>
     *   &lt;dcterms:publisher rdf:resource="http://mertens/knaw/formalorganization_10302a0e-7e4e-4dbb-bce0-59e2a21c8785"/>
     *   &lt;openskos:uuid>5980699b-2c9a-4717-ac30-aed13743cc84&lt;/openskos:uuid>
     *   &lt;/rdf:Description>
     *   &lt;/rdf:RDF>
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
     * @apiError ValidationError The given publisher &lt;publisher's reference>  does not correspond to the tenant code given in the parameter request which refers to the tenant with uri &lt;tenant's uri>.
     * @apiErrorExample ValidationError 
     *   HTTP/1.1 400 Bad request
     *   The given publisher &lt;publisher's reference>  does not correspond to the tenant code given in the parameter request which refers to the tenant with uri &lt;tenant's uri>.
     *
     */
    public function putAction()
    {
        parent::putAction();
    }
    
     /**
     *
     * @apiVersion 1.0.0
     * @apiDescription Delete an OpensSKOS set (former collection) by its uri
     * To activate this API function, the parameter 'optional.authorisation' in application.ini must be set to the 
     * dicrectory where the authorisation procedure is implemented, for instance optional.authorisation = 
     * Custom\Authorisation. If this parameter is absent then 501 is thrown.
     * @api {delete} /api/collections Delete OpensSKOS set
     * @apiName DeleteSet
     * @apiGroup Set
     * @apiParam {String} tenant The institute code for your institute in the OpenSKOS portal
     * @apiParam {String} key A valid API key
     * @apiParam {String} id The uri of the set
     * @apiSuccess {xml} Body
     * @apiSuccessExample Success-Response:
     *    HTTP/1.1 200 OK
     * &lt;?xml version="1.0" encoding="utf-8" ?>
     *   &lt;rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
     *       xmlns:openskos="http://openskos.org/xmlns#"
     *       xmlns:dcterms="http://purl.org/dc/terms/">
     * &lt;rdf:Description rdf:about="http://mertens/knaw/dataset_5980699b-2c9a-4717-ac30-aed13743cc84">
     *   &lt;rdf:type rdf:resource="http://purl.org/dc/dcmitype#Dataset"/>
     *   &lt;openskos:conceptBaseUri>http://example.com/collection-example&lt;/openskos:conceptBaseUri>
     *   &lt;openskos:code>ISO-lang&lt;/openskos:code>
     *   &lt;openskos:webpage rdf:resource="http://ergens"/>
     *   &lt;dcterms:license rdf:resource="http://creativecommons.org/licenses/by/4.0/"/>
     *   &lt;openskos:OAI_baseURL rdf:resource="https://openskos.meertens.knaw.nl/api/ergens1"/>
     *   &lt;dcterms:title xml:lang="en">CLARIN Languages new&lt;/dcterms:title>
     *   &lt;openskos:allow_oai rdf:datatype="http://www.w3.org/2001/XMLSchema#bool">true&lt;/openskos:allow_oai>
     *   &lt;dcterms:publisher rdf:resource="http://mertens/knaw/formalorganization_10302a0e-7e4e-4dbb-bce0-59e2a21c8785"/>
     *   &lt;openskos:uuid>5980699b-2c9a-4717-ac30-aed13743cc84&lt;/openskos:uuid>
     *   &lt;/rdf:Description>
     *   &lt;/rdf:RDF>
     * 
     * @apiError NotFound The requested resource &lt;id> of type http://purl.org/dc/dcmitype#Dataset was not found in the triple store.
     * @apiErrorExample NotFound
     *   HTTP/1.1 404 NotFound
     *   The requested resource &lt;id> of type http://purl.org/dc/dcmitype#Dataset was not found in the triple store.
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
    public function deleteAction()
    {
        parent::deleteAction();
    }
}

