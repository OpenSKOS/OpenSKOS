<?php

require_once 'AbstractController.php';

class Api_InstitutionController extends AbstractController
{
    public function init()
    {
       parent::init();
       $this->fullNameResourceClass = 'OpenSkos2\Api\Tenant';
       $this ->viewpath="institution/";
    }
    
     /**
     *
     * @apiVersion 1.0.0
     * @apiDescription Return a list of OpenSKOS institutions (tenants) 
     *
     * in RDF: /api/institution/  or /api/institution?format=rdf
     * 
     * in JSON: /api/institution?format=json
     * 
     * in JSONP: /api/institution?format=jsonp&callback=myCallback1234
     * 
     * in HTML: /api/institution?format=html
     * 
     * in JSON as name-uri map: /api/institution?shortlist=true&format=json
     * 
     * @api {get} /api/institution Get OpenSKOS institutions
     * @apiName GetInstitutions
     * @apiGroup Institution
     *
     * @apiParam {String=empty, "rdf","html","json","jsonp"}  format If set to jsonp, the request must contain parameter callback as well
     * @apiParam {String} callback If format set to jsonp, must be non-empty
     * @apiParam {String=empty, true, false, 1, 0} shortlist If set to true, then format must be set to json
     *
     * 
     * 
     * @apiSuccess {xml/json/jsonp/html} Body
     * @apiSuccessExample Success-Response:
     *   HTTP/1.1 200 OK 
     * &lt;?xml version="1.0"?>
     * &lt;rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" 
     *   xmlns:dc="http://purl.org/dc/elements/1.1/" 
     *   xmlns:dcterms="http://purl.org/dc/terms/" 
     *   xmlns:skos="http://www.w3.org/2004/02/skos/core#"
     *   xmlns:openskos="http://openskos.org/xmlns#" openskos:numFound="1" openskos:rows="5000" openskos:start="1">
     * &lt;rdf:Description xmlns:vcard="http://www.w3.org/2006/vcard/ns#" rdf:about="http://mertens/knaw/formalorganization_10302a0e-7e4e-4dbb-bce0-59e2a21c8785">
     *  &lt;rdf:type rdf:resource="http://www.w3.org/ns/org#FormalOrganization"/>
     *   &lt;openskos:enableStatussesSystem rdf:datatype="http://www.w3.org/2001/XMLSchema#bool">true&lt;/openskos:enableStatussesSystem>
     *   &lt;openskos:disableSearchInOtherTenants rdf:datatype="http://www.w3.org/2001/XMLSchema#bool">true&lt;/openskos:disableSearchInOtherTenants>
     *   &lt;vcard:ADR rdf:resource="http://node-adr-b9f78264-16e3-4650-8360-caca8f9f91ed"/>
     *   &lt;vcard:ORG rdf:about="http://node-org-a97a9d95-9b53-4ccc-93bf-330cb947ff00">
     *     &lt;vcard:orgname>example.com&lt;/vcard:orgname>
     *  &lt;/vcard:ORG>
     *  &lt;openskos:code>example&lt;/openskos:code>
     *  &lt;openskos:uuid>10302a0e-7e4e-4dbb-bce0-59e2a21c8785&lt;/openskos:uuid>
     *  &lt;/rdf:Description>
     * &lt;/rdf:RDF>
     * 
     * @apiSuccessExample Success-Response for shortlist=true:
     * HTTP/1.1 200 OK
     * {
     * "example.com":"http://mertens/knaw/formalorganization_bd9df26b-313c-445a-ab4e-3467b0429494",
     * "Meertens Institute 3a":"http://mertens/knaw/formalorganization_61762b29-6047-47a7-99ba-ad8ef6010b32"
     * } 
     */
     public function indexAction()
    {
       return parent::indexAction();
    }
   
      /**
     *
     * @apiVersion 1.0.0
     * @apiDescription Return a specific OpenSKOS institution given its uri or uuid
     * 
     * in RDF: /api/institution/{uuid} 
     * 
     * or /api/institution/{uuid.rdf}
     * 
     * or /api/institution?id={uuid}
     * 
     * or /api/institution?id={uuid}&format=rdf
     * 
     * or /api/institution?id={uri}
     * 
     * or /api/institution?id={uri}&format=rdf
     * 
     * in JSON: /api/institution/{uuid.json}
     * 
     * or /api/institution?id={uuid}&format=json
     * 
     * or /api/institution?id={uri}&format=json
     * 
     * in JSONP: /api/institution/{uuid.jsonp}?callback=myCallback1234
     * 
     * or /api/institution?id={uuid}&format=jsonp&callback=myCallback1234
     * 
     * or /api/institution?id={uri}&format=jsonp&callback=myCallback1234
     * 
     * in HTML: /api/institution/{uuid.html}
     * 
     * or /api/institution?id={uuid}&format=html
     * 
     * or /api/institution?id={uri}&format=html
     * 
     *
     *
     * @api {get} /api/institution/{uuid} Get OpenSKOS institution details
     *
     * @apiName GetInstitution
     * @apiGroup Institution
     *
     * @apiParam {String} id uuid or uri
     * @apiParam {String=empty, "rdf","html","json","jsonp"}  format If set to jsonp, the request must contain a non-empty parameter "callback" as well
     * @apiParam {String} callback If format set to jsonp, must be non-empty
    
     * @apiSuccess {xml/json/jsonp/html} Body
     * @apiSuccessExample Success-Response:
     *   HTTP/1.1 200 OK
     * &lt;?xml version="1.0"?>
     * &lt;rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" 
     * xmlns:dc="http://purl.org/dc/elements/1.1/" 
     * xmlns:dcterms="http://purl.org/dc/terms/" 
     * xmlns:skos="http://www.w3.org/2004/02/skos/core#">
     * &lt;rdf:Description xmlns:vcard="http://www.w3.org/2006/vcard/ns#" rdf:about="http://mertens/knaw/formalorganization_10302a0e-7e4e-4dbb-bce0-59e2a21c8785">
     * &lt;rdf:type rdf:resource="http://www.w3.org/ns/org#FormalOrganization"/>
     *   &lt;openskos:enableStatussesSystem rdf:datatype="http://www.w3.org/2001/XMLSchema#bool">true&lt;/openskos:enableStatussesSystem>
     *   &lt;openskos:disableSearchInOtherTenants rdf:datatype="http://www.w3.org/2001/XMLSchema#bool">true&lt;/openskos:disableSearchInOtherTenants>
     *   &lt;vcard:ADR rdf:resource="http://node-adr-b9f78264-16e3-4650-8360-caca8f9f91ed"/>
     *   &lt;vcard:ORG rdf:about="http://node-org-a97a9d95-9b53-4ccc-93bf-330cb947ff00">
     *     &lt;vcard:orgname>example.com&lt;/vcard:orgname>
     *   &lt;/vcard:ORG>
     *  &lt;openskos:code>example&lt;/openskos:code>
     *  &lt;openskos:uuid>10302a0e-7e4e-4dbb-bce0-59e2a21c8785&lt;/openskos:uuid>
     * &lt;/rdf:Description>
     * &lt;/rdf:RDF>
     * 
     * @apiError NotFound The requested resource &lt;id> of type http://www.w3.org/ns/org#FormalOrganization was not found in the triple store.
     * @apiErrorExample Not found:
     *   HTTP/1.1 404 Not Found
     *   The requested resource &lt;id> of type http://www.w3.org/ns/org#FormalOrganization was not found in the triple store.
     */
    
    public function getAction()
    {
        return parent::getAction();
    }
    
    /**
     *
     * @apiVersion 1.0.0
     * @apiDescription Create an OpenSKOS institution
     *
     * Create a new OpenSKOS institution based on the post data.
     * The code and the e-mail, provided in the request body, must be unique, otherwise validator will throw an error.
     *
     *  @apiExample {String} Example request body
     *  <?xml version="1.0" encoding="utf-8" ?>
     *  <rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
     *          xmlns:openskos="http://openskos.org/xmlns#"
     *          xmlns:vcard="http://www.w3.org/2006/vcard/ns#">
     *   <rdf:Description>
     *    <openskos:enableStatussesSystem rdf:datatype="http://www.w3.org/2001/XMLSchema#bool">true&lt;/openskos:enableStatussesSystem>
     *    <openskos:disableSearchInOtherTenants rdf:datatype="http://www.w3.org/2001/XMLSchema#bool">false&lt;/openskos:disableSearchInOtherTenants>
     *     <vcard:ADR rdf:parseType="Resource">
     *       <vcard:Country>Netherlands&lt;/vcard:Country>
     *       <vcard:Pcode>5555&lt;/vcard:Pcode>
     *       <vcard:Locality>Amsterdam Centrum&lt;/vcard:Locality>
     *       <vcard:Street>ErgensAchetrburgwal&lt;/vcard:Street>
     *       </vcard:ADR>
     *     <vcard:EMAIL>info@meertens3.knaw.nl&lt;/vcard:EMAIL>
     *     <vcard:URL>http://meetens.knaw.nl&lt;/vcard:URL>
     *     <vcard:ORG rdf:parseType="Resource">
     *       <vcard:orgunit>XXX&lt;/vcard:orgunit>
     *       <vcard:orgname>Meertens Institute 3&lt;/vcard:orgname>
     *     </vcard:ORG>
     *     <openskos:code>meertens3&lt;/openskos:code>
     *   </rdf:Description>
     * </rdf:RDF>
     *
     * @api {post} /api/institution Create an OpenSKOS institution
     * @apiName CreateInstitution
     * @apiGroup Institution
     *
     * @apiParam {String} key A valid API key
     * @apiParam {String="true","false","1","0"} autoGenerateIdentifiers If set to true (any of "1", "true", "on" and "yes") the concept uri (rdf:about) will be automatically generated.
     *                                           If uri exists in the xml and autoGenerateIdentifiers is true - an error will be thrown.
     *                                           If set to false - the xml must contain uri (rdf:about) and openskos:uuid.
     * @apiSuccess (Created 201) {String} Location Institution uri
     * @apiSuccess (Created 201) {xml} Body
     * @apiSuccessExample Success-Response:
     *   HTTP/1.1 201 Created
     *   &lt;?xml version="1.0" encoding="utf-8" ?>
     *   &lt;rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
     *            xmlns:vcard="http://www.w3.org/2006/vcard/ns#"
     *            xmlns:openskos="http://openskos.org/xmlns#">
     *  &lt;rdf:Description rdf:about="http://mertens/knaw/formalorganization_61762b29-6047-47a7-99ba-ad8ef6010b32">
     *  &lt;rdf:type rdf:resource="http://www.w3.org/ns/org#FormalOrganization"/>
     *    &lt;vcard:URL>http://meetens.knaw.nl&lt;/vcard:URL>
     *    &lt;vcard:ADR rdf:nodeID="genid3">
     *      &lt;vcard:Street>ErgensAchetrburgwal&lt;/vcard:Street>
     *      &lt;vcard:Locality>Amsterdam Centrum&lt;/vcard:Locality>
     *      &lt;vcard:Pcode>5555&lt;/vcard:Pcode>
     *      &lt;vcard:Country>Netherlands&lt;/vcard:Country>
     *    &lt;/vcard:ADR>
     *    &lt;vcard:ORG rdf:nodeID="genid1">
     *      &lt;vcard:orgunit>XXX&lt;/vcard:orgunit>
     *      &lt;vcard:orgname>Meertens Institute 3&lt;/vcard:orgname>
     *    &lt;/vcard:ORG>
     *    &lt;openskos:enableStatussesSystem rdf:datatype="http://www.w3.org/2001/XMLSchema#bool">true&lt;/openskos:enableStatussesSystem>
     *    &lt;openskos:uuid>61762b29-6047-47a7-99ba-ad8ef6010b32&lt;/openskos:uuid>
     *    &lt;openskos:code>meertens3&lt;/openskos:code>
     *    &lt;openskos:disableSearchInOtherTenants rdf:datatype="http://www.w3.org/2001/XMLSchema#bool">false&lt;/openskos:disableSearchInOtherTenants>
     *    &lt;vcard:EMAIL>info@meertens3.knaw.nl&lt;/vcard:EMAIL>
     *    &lt;/rdf:Description>
     * &lt;/rdf:RDF>  
     * 
     * 
     * @apiError MissingKey No user key specified
     * @apiErrorExample MissingKey
     *   HTTP/1.1 412 Precondition Failed
     *   No user key specified
     * 
     * @apiError InstitutionExists The resource with &lt;id> already exists. Use PUT instead.
     * @apiErrorExample SetExists:
     *   HTTP/1.1 400 Bad request
     *   The resource with &lt;id> already exists. Use PUT instead.
     *
     * @apiError GivenURIParameter autoGenerateIdentifiers is set to true, but the provided xml already contains uri (rdf:about).
     * @apiErrorExample GivenURI: 
     *   HTTP/1.1 400 Bad request
     *   Parameter autoGenerateIdentifiers is set to true, but the provided xml already contains uri (rdf:about).
     * 
     * @apiError GivenUUIDParameter autoGenerateIdentifiers is set to true, but the provided xml  already contains uuid.
     * @apiErrorExample GivenUUID:
     *   HTTP/1.1 400 Bad request
     *   Parameter autoGenerateIdentifiers is set to true, but the provided xml already contains uuid.
     * 
     * @apiError ValidationError The resource with the property http://openskos.org/xmlns#code set to &lt;code> has been already registered.
     * @apiErrorExample ValidationError
     *   HTTP/1.1 400 Bad request
     *   The resource with the property http://openskos.org/xmlns#code set to &lt;code> has been already registered.
     */
    
    public function postAction()
    {
       parent::postAction();
    }
    
     /**
     *
     * @apiVersion 1.0.0
     * @apiDescription Update an OpenSKOS institution
     * Update an OpenSKOS institution based on the post data. 
     * The code and the e-mail, provided in the request body, must be unique otherwise the validator will throw an error.
     *
     *  @apiExample {String} Example request
     *  <?xml version="1.0" encoding="utf-8" ?>
     *  <rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
     *          xmlns:openskos="http://openskos.org/xmlns#"
     *          xmlns:vcard="http://www.w3.org/2006/vcard/ns#">
     *  <rdf:Description df:about="http://mertens/knaw/formalorganization_61762b29-6047-47a7-99ba-ad8ef6010b32">
     *    <openskos:enableStatussesSystem rdf:datatype="http://www.w3.org/2001/XMLSchema#bool">true&lt;/openskos:enableStatussesSystem>
     *    <openskos:disableSearchInOtherTenants rdf:datatype="http://www.w3.org/2001/XMLSchema#bool">false&lt;/openskos:disableSearchInOtherTenants>
     *     <vcard:ADR rdf:parseType="Resource">
     *       <vcard:Country>Netherlands&lt;/vcard:Country>
     *       <vcard:Pcode>5555&lt;/vcard:Pcode>
     *       <vcard:Locality>Amsterdam Centrum&lt;/vcard:Locality>
     *       <vcard:Street>ErgensAchetrburgwal&lt;/vcard:Street>
     *     </vcard:ADR>
     *     <vcard:EMAIL>info@meertens3.knaw.nl&lt;/vcard:EMAIL>
     *     <vcard:URL>http://meetens.knaw.nl&lt;/vcard:URL>
     *     <vcard:ORG rdf:parseType="Resource">
     *       <vcard:orgunit>XXX&lt;/vcard:orgunit>
     *       <vcard:orgname>Meertens Institute 3 upd&lt;/vcard:orgname>
     *     </vcard:ORG>
     *     <openskos:code>meertens3&lt;/openskos:code>
     *     <openskos:uuid>61762b29-6047-47a7-99ba-ad8ef6010b32&lt;/openskos:uuid>
     *   </rdf:Description>
     * </rdf:RDF>
     *
     * @api {put} /api/institution Update an OpenSKOS institution
     * @apiName UpdateInstitution
     * @apiGroup Institution
     *
     * @apiParam {String} key A valid API key
     * @apiSuccess {xml} Body
     * @apiSuccessExample Success-Response:
     *   HTTP/1.1 200 OK
     *   &lt;?xml version="1.0" encoding="utf-8" ?>
     *   &lt;rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
     *            xmlns:vcard="http://www.w3.org/2006/vcard/ns#"
     *            xmlns:openskos="http://openskos.org/xmlns#">
     *  &lt;rdf:Description rdf:about="http://mertens/knaw/formalorganization_61762b29-6047-47a7-99ba-ad8ef6010b32">
     *  &lt;rdf:type rdf:resource="http://www.w3.org/ns/org#FormalOrganization"/>
     *    &lt;vcard:URL>http://meetens.knaw.nl&lt;/vcard:URL>
     *    &lt;vcard:ADR rdf:nodeID="genid3">
     *      &lt;vcard:Street>ErgensAchetrburgwal&lt;/vcard:Street>
     *      &lt;vcard:Locality>Amsterdam Centrum&lt;/vcard:Locality>
     *      &lt;vcard:Pcode>5555&lt;/vcard:Pcode>
     *      &lt;vcard:Country>Netherlands&lt;/vcard:Country>
     *    &lt;/vcard:ADR>
     *    &lt;vcard:ORG rdf:nodeID="genid1">
     *      &lt;vcard:orgunit>XXX&lt;/vcard:orgunit>
     *       &lt;vcard:orgname>Meertens Institute 3 upd&lt;/vcard:orgname>
     *    &lt;/vcard:ORG>
     *    &lt;openskos:enableStatussesSystem rdf:datatype="http://www.w3.org/2001/XMLSchema#bool">true&lt;/openskos:enableStatussesSystem>
     *    &lt;openskos:uuid>61762b29-6047-47a7-99ba-ad8ef6010b32&lt;/openskos:uuid>
     *    &lt;openskos:code>meertens3&lt;/openskos:code>
     *    &lt;openskos:disableSearchInOtherTenants rdf:datatype="http://www.w3.org/2001/XMLSchema#bool">false&lt;/openskos:disableSearchInOtherTenants>
     *    &lt;vcard:EMAIL>info@meertens3.knaw.nl&lt;/vcard:EMAIL>
     *   &lt;/rdf:Description>
     * &lt;/rdf:RDF>  
     * 
     * @apiError MissingKey No user key specified
     * @apiErrorExample MissingKey
     *   HTTP/1.1 412 Precondition Failed
     *   No user key specified
     * 
     * @apiError MissingUri Missed uri (rdf:about)!
     * @apiErrorExample MissingUri:
     *   HTTP/1.1 400 Bad Request
     *   Missed uri (rdf:about)! 
     * 
     * @apiError ValidationError You cannot change UUID of the resouce. Keep it &lt;uuid_of_resource_uri>
     * @apiErrorExample ChangedOrMissingUuid: 
     *   HTTP/1.1 400 Bad Request
     *   You cannot change UUID of the resouce. Keep it &lt;uuid_of_resource_uri>
     *
     * @apiError ValidationError The resource with the property http://www.w3.org/2006/vcard/ns#EMAIL set to &lt;email> has been already registered.
     * @apiErrorExample ValidationError 
     *   HTTP/1.1 400 Bad Request
     *   The resource with the property http://www.w3.org/2006/vcard/ns#EMAIL set to &lt;email> has been already registered.
     */
    public function putAction()
    {
        parent::putAction();
    }
    
     /**
     *
     * @apiVersion 1.0.0
     * @apiDescription Delete an OpensSKOS Institution by its uri
     * @api {delete} /api/institution Delete OpensSKOS institution
     * @apiName DeleteInstitution
     * @apiGroup Institution
     * @apiParam {String} key A valid API key
     * @apiParam {String} uri The uri of the institution
     * @apiSuccess {xml} Body
     * @apiSuccessExample Success-Response:
     *    HTTP/1.1 200 OK
     *   &lt;?xml version="1.0" encoding="utf-8" ?>
     *   &lt;rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
     *            xmlns:vcard="http://www.w3.org/2006/vcard/ns#"
     *            xmlns:openskos="http://openskos.org/xmlns#">
     *  &lt;rdf:Description rdf:about="http://mertens/knaw/formalorganization_61762b29-6047-47a7-99ba-ad8ef6010b32">
     *  &lt;rdf:type rdf:resource="http://www.w3.org/ns/org#FormalOrganization"/>
     *    &lt;vcard:URL>http://meetens.knaw.nl&lt;/vcard:URL>
     *    &lt;vcard:ADR rdf:nodeID="genid3">
     *      &lt;vcard:Street>ErgensAchetrburgwal&lt;/vcard:Street>
     *      &lt;vcard:Locality>Amsterdam Centrum&lt;/vcard:Locality>
     *      &lt;vcard:Pcode>5555&lt;/vcard:Pcode>
     *      &lt;vcard:Country>Netherlands&lt;/vcard:Country>
     *    &lt;/vcard:ADR>
     *    &lt;vcard:ORG rdf:nodeID="genid1">
     *      &lt;vcard:orgunit>XXX&lt;/vcard:orgunit>
     *       &lt;vcard:orgname>Meertens Institute 3 upd&lt;/vcard:orgname>
     *    &lt;/vcard:ORG>
     *    &lt;openskos:enableStatussesSystem rdf:datatype="http://www.w3.org/2001/XMLSchema#bool">true&lt;/openskos:enableStatussesSystem>
     *    &lt;openskos:uuid>61762b29-6047-47a7-99ba-ad8ef6010b32&lt;/openskos:uuid>
     *    &lt;openskos:code>meertens3&lt;/openskos:code>
     *    &lt;openskos:disableSearchInOtherTenants rdf:datatype="http://www.w3.org/2001/XMLSchema#bool">false&lt;/openskos:disableSearchInOtherTenants>
     *    &lt;vcard:EMAIL>info@meertens3.knaw.nl&lt;/vcard:EMAIL>
     *   &lt;/rdf:Description>
     * &lt;/rdf:RDF> 
     *  
     * @apiError NotFound The requested resource &lt;id> of type http://www.w3.org/ns/org#FormalOrganization was not found in the triple store.
     * @apiErrorExample NotFound
     *   HTTP/1.1 404 NotFound
     *   The requested resource &lt;id> of type http://www.w3.org/ns/org#FormalOrganization was not found in the triple store.
     *
     * @apiError MissingKey No user key specified
     * @apiErrorExample MissingKey
     *   HTTP/1.1 412 Precondition Failed
     *   No user key specified
     */
    public function deleteAction()
    {
        parent::deleteAction();
    }
    
    
}
