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
     * @api {get} /api/institution Get SKOS institutions
     * @apiName GetInstitutions
     * @apiGroup Institution
     *
     * @apiParam {String=empty, "rdf","html","json","jsonp"}  format If set to jsonp, must contain parameter callback as well
     * @apiParam {String} callback If format set to jsonp, must be non-empty
     * @apiSuccess (200) OK
     * @apiSuccessExample {String} Success-Response
     *   HTTP/1.1 200 OK 
     * <?xml version="1.0"?>
     * <rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" 
     *   xmlns:dc="http://purl.org/dc/elements/1.1/" 
     *   xmlns:dcterms="http://purl.org/dc/terms/" 
     *   xmlns:skos="http://www.w3.org/2004/02/skos/core#"
     *   xmlns:openskos="http://openskos.org/xmlns#" openskos:numFound="1" openskos:rows="5000" openskos:start="1">
     * <rdf:Description xmlns:vcard="http://www.w3.org/2006/vcard/ns#" rdf:about="http://mertens/knaw/formalorganization_10302a0e-7e4e-4dbb-bce0-59e2a21c8785">
     *  <rdf:type rdf:resource="http://www.w3.org/ns/org#FormalOrganization"/>
     *   <openskos:enableStatussesSystem rdf:datatype="http://www.w3.org/2001/XMLSchema#bool">true</openskos:enableStatussesSystem>
     *   <openskos:disableSearchInOtherTenants rdf:datatype="http://www.w3.org/2001/XMLSchema#bool">true</openskos:disableSearchInOtherTenants>
     *   <vcard:ADR rdf:resource="http://node-adr-b9f78264-16e3-4650-8360-caca8f9f91ed"/>
     *   <vcard:ORG rdf:about="http://node-org-a97a9d95-9b53-4ccc-93bf-330cb947ff00">
     *     <vcard:orgname>example.com</vcard:orgname>
     *  </vcard:ORG>
     *  <openskos:code>example</openskos:code>
     *  <openskos:uuid>10302a0e-7e4e-4dbb-bce0-59e2a21c8785</openskos:uuid>
     *  </rdf:Description>
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
     * @apiDescription Return a specific OpenSKOS institution by its uri or uuid
     *
     * @api {get} /api/institution/ Get OpenSKOS institution details
     * @api {get} /api/institution/{uuid}[.rdf, .html, .json, .jsonp]  Get SKOS collection details
     
     * @apiName GetSkosCollection
     * @apiGroup SkosCollection
     *
     * @apiParam {String} id (uuid or uri)
     * @apiParam {String=empty, "rdf","html","json","jsonp"}  format If set to jsonp, must contain parameter "callback" as well
     * @apiParam {String} callback If format set to jsonp, must be non-empty
    
     * @apiSuccess (200) OK
     * @apiSuccessExample {String} Success-Response
     *   HTTP/1.1 200 OK
     * <?xml version="1.0"?>
     * <rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" 
     * xmlns:dc="http://purl.org/dc/elements/1.1/" 
     * xmlns:dcterms="http://purl.org/dc/terms/" 
     * xmlns:skos="http://www.w3.org/2004/02/skos/core#">
     * <rdf:Description xmlns:vcard="http://www.w3.org/2006/vcard/ns#" rdf:about="http://mertens/knaw/formalorganization_10302a0e-7e4e-4dbb-bce0-59e2a21c8785">
     * <rdf:type rdf:resource="http://www.w3.org/ns/org#FormalOrganization"/>
     *   <openskos:enableStatussesSystem rdf:datatype="http://www.w3.org/2001/XMLSchema#bool">true</openskos:enableStatussesSystem>
     *   <openskos:disableSearchInOtherTenants rdf:datatype="http://www.w3.org/2001/XMLSchema#bool">true</openskos:disableSearchInOtherTenants>
     *   <vcard:ADR rdf:resource="http://node-adr-b9f78264-16e3-4650-8360-caca8f9f91ed"/>
     *   <vcard:ORG rdf:about="http://node-org-a97a9d95-9b53-4ccc-93bf-330cb947ff00">
     *     <vcard:orgname>example.com</vcard:orgname>
     *   </vcard:ORG>
     *  <openskos:code>example</openskos:code>
     *  <openskos:uuid>10302a0e-7e4e-4dbb-bce0-59e2a21c8785</openskos:uuid>
     * </rdf:Description>
     * </rdf:RDF>
     * @apiError NotFound {String} The requested resource <id> of type http://www.w3.org/ns/org#FormalOrganization was not found in the triple store.
     * @apiErrorExample Not found:
     *   HTTP/1.1 404 Not Found
     *   The requested resource <id> of type http://www.w3.org/ns/org#FormalOrganization was not found in the triple store.
     */
    
    public function getAction()
    {
        parent::getAction();
    }
    
    /**
     *
     * @apiVersion 1.0.0
     * @apiDescription Create an OpenSKOS institution
    
     * Create a new OpenSKOS institution based on the post data
     *
     *  @apiExample {String} Example request
     *  <?xml version="1.0" encoding="utf-8" ?>
     *  <rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
     *          xmlns:openskos="http://openskos.org/xmlns#"
     *          xmlns:vcard="http://www.w3.org/2006/vcard/ns#">
     *   <rdf:Description>
     *    <openskos:enableStatussesSystem rdf:datatype="http://www.w3.org/2001/XMLSchema#bool">true</openskos:enableStatussesSystem>
     *    <openskos:disableSearchInOtherTenants rdf:datatype="http://www.w3.org/2001/XMLSchema#bool">false</openskos:disableSearchInOtherTenants>
     *     <vcard:ADR rdf:parseType="Resource">
     *       <vcard:Country>Netherlands</vcard:Country>
     *       <vcard:Pcode>5555</vcard:Pcode>
     *       <vcard:Locality>Amsterdam Centrum</vcard:Locality>
     *       <vcard:Street>ErgensAchetrburgwal</vcard:Street>
     *     </vcard:ADR>
     *     <vcard:EMAIL>info@meertens3.knaw.nl</vcard:EMAIL>
     *     <vcard:URL>http://meetens.knaw.nl</vcard:URL>
     *     <vcard:ORG rdf:parseType="Resource">
     *       <vcard:orgunit>XXX</vcard:orgunit>
     *       <vcard:orgname>Meertens Institute 3</vcard:orgname>
     *     </vcard:ORG>
     *     <openskos:code>meertens3</openskos:code>
     *   </rdf:Description>
     * </rdf:RDF>
     *
     * @api {post} /api/set Create an OpenSKOS institution
     * @apiName CreateInstitution
     * @apiGroup Institution
     *
     * @apiParam {String} key A valid API key
     * @apiParam {String="true","false","1","0"} autoGenerateIdentifiers If set to true (any of "1", "true", "on" and "yes") the concept uri (rdf:about) will be automatically generated.
     *                                           If uri exists in the xml and autoGenerateIdentifiers is true - an error will be thrown.
     *                                           If set to false - the xml must contain uri (rdf:about).
     * @apiSuccess (201) {String} Set uri
     * @apiSuccessExample {String} Success-Response
     *   HTTP/1.1 201 Created
     *   <?xml version="1.0" encoding="utf-8" ?>
     *   <rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
     *            xmlns:vcard="http://www.w3.org/2006/vcard/ns#"
     *            xmlns:openskos="http://openskos.org/xmlns#">
     *  <rdf:Description rdf:about="http://mertens/knaw/formalorganization_61762b29-6047-47a7-99ba-ad8ef6010b32">
     *  <rdf:type rdf:resource="http://www.w3.org/ns/org#FormalOrganization"/>
     *    <vcard:URL>http://meetens.knaw.nl</vcard:URL>
     *    <vcard:ADR rdf:nodeID="genid3">
     *      <vcard:Street>ErgensAchetrburgwal</vcard:Street>
     *      <vcard:Locality>Amsterdam Centrum</vcard:Locality>
     *      <vcard:Pcode>5555</vcard:Pcode>
     *      <vcard:Country>Netherlands</vcard:Country>
     *    </vcard:ADR>
     *    <vcard:ORG rdf:nodeID="genid1">
     *      <vcard:orgunit>XXX</vcard:orgunit>
     *       <vcard:orgname>Meertens Institute 3</vcard:orgname>
     *    </vcard:ORG>
     *    <openskos:enableStatussesSystem rdf:datatype="http://www.w3.org/2001/XMLSchema#bool">true</openskos:enableStatussesSystem>
     *    <openskos:uuid>61762b29-6047-47a7-99ba-ad8ef6010b32</openskos:uuid>
     *    <openskos:code>meertens3</openskos:code>
     *    <openskos:disableSearchInOtherTenants rdf:datatype="http://www.w3.org/2001/XMLSchema#bool">false</openskos:disableSearchInOtherTenants>
     *    <vcard:EMAIL>info@meertens3.knaw.nl</vcard:EMAIL>
     *   </rdf:Description>
     * </rdf:RDF>  
     * @apiError MissingKey {String} No key specified
     * @apiErrorExample MissingKey:
     *   HTTP/1.1 412 Precondition Failed
     *   No key specified
     * @apiError SetExists {String} X-Error-Msg: The resource with <id> already exists. Use PUT instead.
     * @apiErrorExample SetExists:
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
     * @apiDescription Update an OpenSKOS institution
     * Update an OpenSKOS institution based on the post data
     *
     *  @apiExample {String} Example request
     *  <?xml version="1.0" encoding="utf-8" ?>
     *  <rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
     *          xmlns:openskos="http://openskos.org/xmlns#"
     *          xmlns:vcard="http://www.w3.org/2006/vcard/ns#">
     *  <rdf:Description>
     *    <openskos:enableStatussesSystem rdf:datatype="http://www.w3.org/2001/XMLSchema#bool">true</openskos:enableStatussesSystem>
     *    <openskos:disableSearchInOtherTenants rdf:datatype="http://www.w3.org/2001/XMLSchema#bool">false</openskos:disableSearchInOtherTenants>
     *     <vcard:ADR rdf:parseType="Resource">
     *       <vcard:Country>Netherlands</vcard:Country>
     *       <vcard:Pcode>5555</vcard:Pcode>
     *       <vcard:Locality>Amsterdam Centrum</vcard:Locality>
     *       <vcard:Street>ErgensAchetrburgwal</vcard:Street>
     *     </vcard:ADR>
     *     <vcard:EMAIL>info@meertens3.knaw.nl</vcard:EMAIL>
     *     <vcard:URL>http://meetens.knaw.nl</vcard:URL>
     *     <vcard:ORG rdf:parseType="Resource">
     *       <vcard:orgunit>XXX</vcard:orgunit>
     *       <vcard:orgname>Meertens Institute 3 upd</vcard:orgname>
     *     </vcard:ORG>
     *     <openskos:code>meertens3</openskos:code>
     *   </rdf:Description>
     * </rdf:RDF>
     *
     * @api {post} /api/set Update an OpenSKOS institution
     * @apiName UpdateInstitution
     * @apiGroup Institution
     *
     * @apiParam {String} key A valid API key
     * @apiSuccess (200) 
     * @apiSuccessExample {String} Success-Response
     *   HTTP/1.1 200 OK
     *   <?xml version="1.0" encoding="utf-8" ?>
     *   <rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
     *            xmlns:vcard="http://www.w3.org/2006/vcard/ns#"
     *            xmlns:openskos="http://openskos.org/xmlns#">
     *  <rdf:Description rdf:about="http://mertens/knaw/formalorganization_61762b29-6047-47a7-99ba-ad8ef6010b32">
     *  <rdf:type rdf:resource="http://www.w3.org/ns/org#FormalOrganization"/>
     *    <vcard:URL>http://meetens.knaw.nl</vcard:URL>
     *    <vcard:ADR rdf:nodeID="genid3">
     *      <vcard:Street>ErgensAchetrburgwal</vcard:Street>
     *      <vcard:Locality>Amsterdam Centrum</vcard:Locality>
     *      <vcard:Pcode>5555</vcard:Pcode>
     *      <vcard:Country>Netherlands</vcard:Country>
     *    </vcard:ADR>
     *    <vcard:ORG rdf:nodeID="genid1">
     *      <vcard:orgunit>XXX</vcard:orgunit>
     *       <vcard:orgname>Meertens Institute 3 upd</vcard:orgname>
     *    </vcard:ORG>
     *    <openskos:enableStatussesSystem rdf:datatype="http://www.w3.org/2001/XMLSchema#bool">true</openskos:enableStatussesSystem>
     *    <openskos:uuid>61762b29-6047-47a7-99ba-ad8ef6010b32</openskos:uuid>
     *    <openskos:code>meertens3</openskos:code>
     *    <openskos:disableSearchInOtherTenants rdf:datatype="http://www.w3.org/2001/XMLSchema#bool">false</openskos:disableSearchInOtherTenants>
     *    <vcard:EMAIL>info@meertens3.knaw.nl</vcard:EMAIL>
     *   </rdf:Description>
     * </rdf:RDF>  
     * @apiError MissingKey {String} No key specified
     * @apiErrorExample MissingKey:
     *   HTTP/1.1 412 Precondition Failed
     *   No key specified
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
     * @apiParam {String} uri The uri of the set
     * @apiSuccess (202) Accepted
     *   <?xml version="1.0" encoding="utf-8" ?>
     *   <rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
     *            xmlns:vcard="http://www.w3.org/2006/vcard/ns#"
     *            xmlns:openskos="http://openskos.org/xmlns#">
     *  <rdf:Description rdf:about="http://mertens/knaw/formalorganization_61762b29-6047-47a7-99ba-ad8ef6010b32">
     *  <rdf:type rdf:resource="http://www.w3.org/ns/org#FormalOrganization"/>
     *    <vcard:URL>http://meetens.knaw.nl</vcard:URL>
     *    <vcard:ADR rdf:nodeID="genid3">
     *      <vcard:Street>ErgensAchetrburgwal</vcard:Street>
     *      <vcard:Locality>Amsterdam Centrum</vcard:Locality>
     *      <vcard:Pcode>5555</vcard:Pcode>
     *      <vcard:Country>Netherlands</vcard:Country>
     *    </vcard:ADR>
     *    <vcard:ORG rdf:nodeID="genid1">
     *      <vcard:orgunit>XXX</vcard:orgunit>
     *       <vcard:orgname>Meertens Institute 3 upd</vcard:orgname>
     *    </vcard:ORG>
     *    <openskos:enableStatussesSystem rdf:datatype="http://www.w3.org/2001/XMLSchema#bool">true</openskos:enableStatussesSystem>
     *    <openskos:uuid>61762b29-6047-47a7-99ba-ad8ef6010b32</openskos:uuid>
     *    <openskos:code>meertens3</openskos:code>
     *    <openskos:disableSearchInOtherTenants rdf:datatype="http://www.w3.org/2001/XMLSchema#bool">false</openskos:disableSearchInOtherTenants>
     *    <vcard:EMAIL>info@meertens3.knaw.nl</vcard:EMAIL>
     *   </rdf:Description>
     * </rdf:RDF>  
     * @apiError Not found {String} The requested resource <id> of type http://www.w3.org/ns/org#FormalOrganization was not found in the triple store.
     * @apiErrorExample NotFound
     *   HTTP/1.1 404 NotFound
     *   The requested resource <id> of type http://www.w3.org/ns/org#FormalOrganization was not found in the triple store.
     * @apiError MissingKey {String} No key specified
     * @apiErrorExample MissingKey
     *   HTTP/1.1 412 Precondition Failed
     *   No key specified
      */
    public function deleteAction()
    {
        parent::deleteAction();
    }
    
    
}
