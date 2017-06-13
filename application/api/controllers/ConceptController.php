<?php

/**
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

require_once 'FindConceptsController.php';

// Meertens: here was no code changes of picturae after October 2016, only documentation and formatting
// which are taken in this merged file.
// The code is basically identicall to the picturae version.

class Api_ConceptController extends Api_FindConceptsController
{

    public function init()
    {
        parent::init();
        $this ->trow501 = false;
    }

    /**
     *
     * @apiVersion 1.0.0

     * @apiDescription Create a SKOS Concept
     *
     * Create a new SKOS concept based on the post data.
     *
     * If the tenant is configured to use the new SKOS-XL labels then
     * the new concept also needs to have its labels (pref, alt and hidden) defined in SKOS-XL format.
     *
     * e.g.
     *
     * &lt;skosxl:prefLabel>
     *   &lt;rdf:Description rdf:about="http://data.beeldengeluid.nl/api/labels/d0e34b9f-d31d-4858-b4e7-3bcbdd377c26">
     *   &lt;rdf:type rdf:resource="http://www.w3.org/2008/05/skos-xl#Label"/>
     *   &lt;skosxl:literalForm xml:lang="nl">doodstraf&lt;/skosxl:literalForm>
     *   &lt;openskos:tenant>beg&lt;/openskos:tenant>
     *   &lt;/rdf:Description>
     * &lt;/skosxl:prefLabel>
     *
     * instead of as defined in the example.
     *
     * @apiExample {String} Example request
     *  <?xml version="1.0"?>
     *  <rdf:RDF
     *    xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
     *    xmlns:openskos="http://openskos.org/xmlns#"
     *    xmlns:skos="http://www.w3.org/2004/02/skos/core#">
     *    <rdf:Description rdf:about="http://data.beeldengeluid.nl/gtaa/28586">
     *      <rdf:type rdf:resource="http://www.w3.org/2004/02/skos/core#Concept"/>
     *      <skos:prefLabel xml:lang="nl">doodstraf</skos:prefLabel>
     *      <skos:inScheme rdf:resource="http://data.beeldengeluid.nl/gtaa/Onderwerpen"/>
     *      <skos:broader rdf:resource="http://data.beeldengeluid.nl/gtaa/24842"/>
     *      <skos:related rdf:resource="http://data.beeldengeluid.nl/gtaa/25652"/>
     *      <skos:related rdf:resource="http://data.beeldengeluid.nl/gtaa/24957"/>
     *      <skos:altLabel xml:lang="nl">kruisigingen</skos:altLabel>
     *      <skos:broader rdf:resource="http://data.beeldengeluid.nl/gtaa/27731"/>
     *      <skos:related rdf:resource="http://data.beeldengeluid.nl/gtaa/28109"/>
     *      <skos:inScheme rdf:resource="http://data.beeldengeluid.nl/gtaa/GTAA"/>
     *      <skos:notation>28586</skos:notation>
     *      <openskos:uuid>GTAA_-2571_b6e99d21-fdd8-5071-5179-1a2876cda0e8<openskos:uuid>
     *    </rdf:Description>
     *  </rdf:RDF>
     * 
     * 
     * @api {post} /api/concept Create SKOS concept
     * @apiName CreateConcept
     * @apiGroup Concept
     *
     * @apiParam {String} tenant The institute code for your institute in the OpenSKOS portal
     * @apiParam {String} key A valid API key
     * @apiParam {String} set A set's code
     * @apiParam {String} collection Obsolete parameter for backward compatibility. A set's code
     * @apiParam {String="true","false","1","0"} autoGenerateIdentifiers If set to true (any of "1", "true", "on" and "yes") the concept uri (rdf:about) and uuid will be automatically generated.
     *                                           If uri exists in the xml and autoGenerateIdentifiers is true then an error will be thrown.
     *                                           If the parameter set to false then the xml must contain uri (rdf:about) and uuid.
     * @apiSuccess (201) {String} Location New concept uri
     * @apiSuccess (201) {xml} Body
     * @apiSuccessExample Success-Response:
     *   HTTP/1.1 201 Created
     *   &lt;?xml version="1.0"?>
     *   &lt;rdf:RDF xmlns:dc="http://purl.org/dc/elements/1.1/"
     *      xmlns:dcterms="http://purl.org/dc/terms/"
     *      xmlns:openskos="http://openskos.org/xmlns#"
     *      xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
     *      xmlns:skos="http://www.w3.org/2004/02/skos/core#">
     *   &lt;rdf:Description rdf:about="http://data.beeldengeluid.nl/gtaa/285863243243224">
     *           &lt;rdf:type rdf:resource="http://www.w3.org/2004/02/skos/core#Concept"/>
     *           &lt;skos:prefLabel xml:lang="nl">doodstraff&lt;/skos:prefLabel>
     *           &lt;skos:inScheme rdf:resource="http://data.beeldengeluid.nl/gtaa/Onderwerpen"/>
     *           &lt;skos:broader rdf:resource="http://data.beeldengeluid.nl/gtaa/24842"/>
     *           &lt;skos:related rdf:resource="http://data.beeldengeluid.nl/gtaa/25652"/>
     *           &lt;skos:related rdf:resource="http://data.beeldengeluid.nl/gtaa/24957"/>
     *           &lt;skos:altLabel xml:lang="nl">kruisigingen&lt;/skos:altLabel>
     *           &lt;skos:broader rdf:resource="http://data.beeldengeluid.nl/gtaa/27731"/>
     *           &lt;skos:related rdf:resource="http://data.beeldengeluid.nl/gtaa/28109"/>
     *           &lt;skos:inScheme rdf:resource="http://data.beeldengeluid.nl/gtaa/GTAA"/>
     *           &lt;skos:notation>285863243243224&lt;/skos:notation>
     *           &lt;openskos:status>candidate&lt;/openskos:status>
     *           &lt;openskos:uuid>GTAA_-2571_b6e99d21-fdd8-5071-5179-1a2876cda0e8&lt;/openskos:uuid>
     *           &lt;openskos:set rdf:resource="http://data.beeldengeluid.nl/gtaa/"/>
     *           &lt;openskos:tenant rdf:resource="http://data.beeldengeluid.nl"/>
     *           &lt;dcterms:dateSubmitted rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2017-02-24T14:19:57+00:00&lt;/dcterms:dateSubmitted>
     *           &lt;dcterms:creator rdf:resource="http://localhost/clavas/public/api/users/f122deab-755a-4f67-8502-7cd9bfd70ec5"/>       
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
     * @apiError MissingTenant No set (former tenant collection) specified
     * @apiErrorExample MissingSet
     *   HTTP/1.1 412 Precondition Failed
     *   No set (former tenant collection) specified
     * 
     * @apiError ConceptExists The resource with uri &lt;concept uri&gt; already exists. Use PUT instead.
     * @apiErrorExample ConceptExists
     *   HTTP/1.1 400 Bad request
     *   The resource with uri &lt;concept uri&gt; already exists. Use PUT instead.
     * 
     * @apiError ValidationError The pref label already exists in that concept scheme.
     * @apiErrorExample ValidationError
     *   HTTP/1.1 400 Bad request
     *   The pref label already exists in that concept scheme.
     * 
     * @apiError ValidationError The resource (of type http://www.w3.org/2004/02/skos/core#ConceptScheme) referred by  uri <concepts schema uri> is not found,
     * @apiErrorExample ValidationError
     *   HTTP/1.1 400 Bad request
     *   The resource (of type http://www.w3.org/2004/02/skos/core#ConceptScheme) referred by  uri <concepts schema uri> is not found.
     */
    public function postAction()
    {
      $this->throw501 =false; 
      parent::postAction();
    }

    /**
     *
     * @apiVersion 1.0.0
     * @apiDescription Update a specified SKOS concept with the new data provided in the request body.
     *
     * If the tenant is configured to use the new SKOS-XL labels then
     * the new concept also needs to have its labels (pref, alt and hidden) defined in SKOS-XL format.
     *
     * e.g.
     *
     * &lt;skosxl:prefLabel>
     *   &lt;rdf:Description rdf:about="http://data.beeldengeluid.nl/api/labels/d0e34b9f-d31d-4858-b4e7-3bcbdd377c26">
     *   &lt;rdf:type rdf:resource="http://www.w3.org/2008/05/skos-xl#Label"/>
     *   &lt;skosxl:literalForm xml:lang="nl">doodstraf&lt;/skosxl:literalForm>
     *   &lt;openskos:tenant>beg&lt;/openskos:tenant>
     *   &lt;/rdf:Description>
     * &lt;/skosxl:prefLabel>
     *
     * instead of as defined in the example.
     *
     * @apiExample {String} Example request
     *  <?xml version="1.0"?>
     *  <rdf:RDF
     *    xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
     *    xmlns:openskos="http://openskos.org/xmlns#"
     *    xmlns:skos="http://www.w3.org/2004/02/skos/core#">
     *    <rdf:Description rdf:about="http://data.beeldengeluid.nl/gtaa/28586">
     *      <rdf:type rdf:resource="http://www.w3.org/2004/02/skos/core#Concept"/>
     *      <skos:prefLabel xml:lang="nl">doodstraf</skos:prefLabel>
     *      <skos:inScheme rdf:resource="http://data.beeldengeluid.nl/gtaa/Onderwerpen"/>
     *      <skos:broader rdf:resource="http://data.beeldengeluid.nl/gtaa/24842"/>
     *      <skos:related rdf:resource="http://data.beeldengeluid.nl/gtaa/25652"/>
     *      <skos:related rdf:resource="http://data.beeldengeluid.nl/gtaa/24957"/>
     *      <skos:altLabel xml:lang="nl">kruisigingen</skos:altLabel>
     *      <skos:broader rdf:resource="http://data.beeldengeluid.nl/gtaa/27731"/>
     *      <skos:related rdf:resource="http://data.beeldengeluid.nl/gtaa/28109"/>
     *      <skos:inScheme rdf:resource="http://data.beeldengeluid.nl/gtaa/GTAA"/>
     *      <skos:notation>28586</skos:notation>
     *      <openskos:uuid>GTAA_-2571_b6e99d21-fdd8-5071-5179-1a2876cda0e8<openskos:uuid>
     *    </rdf:Description>
     *  </rdf:RDF>
     *
     * @apiName UpdateConcept
     * @apiGroup Concept
     *
     * @api {put} /api/concept Update SKOS concept
     * @apiParam {String} tenant The institute code for your institute in the OpenSKOS portal
     * @apiParam {String} key A valid API key
     * @apiSuccess {xml} Body
     * @apiSuccessExample Success-Response:
     *   HTTP/1.1 200 Ok
     *   &lt;?xml version="1.0"?>
     *   &lt;rdf:RDF xmlns:dc="http://purl.org/dc/elements/1.1/"
     *      xmlns:dcterms="http://purl.org/dc/terms/"
     *      xmlns:openskos="http://openskos.org/xmlns#"
     *      xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
     *      xmlns:skos="http://www.w3.org/2004/02/skos/core#">
     *   &lt;rdf:Description rdf:about="http://data.beeldengeluid.nl/gtaa/285863243243224">
     *           &lt;rdf:type rdf:resource="http://www.w3.org/2004/02/skos/core#Concept"/>
     *           &lt;skos:prefLabel xml:lang="nl">doodstraff&lt;/skos:prefLabel>
     *           &lt;skos:inScheme rdf:resource="http://data.beeldengeluid.nl/gtaa/Onderwerpen"/>
     *           &lt;skos:broader rdf:resource="http://data.beeldengeluid.nl/gtaa/24842"/>
     *           &lt;skos:related rdf:resource="http://data.beeldengeluid.nl/gtaa/25652"/>
     *           &lt;skos:related rdf:resource="http://data.beeldengeluid.nl/gtaa/24957"/>
     *           &lt;skos:altLabel xml:lang="nl">kruisigingen&lt;/skos:altLabel>
     *           &lt;skos:broader rdf:resource="http://data.beeldengeluid.nl/gtaa/27731"/>
     *           &lt;skos:related rdf:resource="http://data.beeldengeluid.nl/gtaa/28109"/>
     *           &lt;skos:inScheme rdf:resource="http://data.beeldengeluid.nl/gtaa/GTAA"/>
     *           &lt;skos:notation>285863243243224&lt;/skos:notation>
     *           &lt;openskos:status>candidate&lt;/openskos:status>
     *           &lt;openskos:uuid>GTAA_-2571_b6e99d21-fdd8-5071-5179-1a2876cda0e8 &lt;openskos:uuid>
     *           &lt;openskos:set rdf:resource="http://data.beeldengeluid.nl/gtaa/"/>
     *           &lt;openskos:tenant rdf:resource="http://data.beeldengeluid.nl"/>
     *           &lt;dcterms:dateSubmitted rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2017-02-24T14:19:57+00:00&lt;/dcterms:dateSubmitted>
     *           &lt;dcterms:creator rdf:resource="http://localhost/clavas/public/api/users/f122deab-755a-4f67-8502-7cd9bfd70ec5"/>
     *           &lt;dcterms:modified rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2017-02-24T14:33:45+00:00&lt;/dcterms:modified>
     *           &lt;dcterms:contributor rdf:resource="http://localhost/clavas/public/api/users/f122deab-755a-4f67-8502-7cd9bfd70ec5"/>
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
     * @apiError ConceptExists The resource with uri &lt;concept uri&gt; already exists. Use PUT instead.
     * @apiErrorExample ConceptExists
     *   HTTP/1.1 400 Bad request
     *   The resource with uri &lt;concept uri&gt; already exists. Use PUT instead.
     * 
     * @apiError ValidationError The pref label already exists in that concept scheme.
     * @apiErrorExample ValidationError
     *   HTTP/1.1 400 Bad request
     *   TThe pref label already exists in that concept scheme.
     * 
     * @apiError ValidationError The resource (of type http://www.w3.org/2004/02/skos/core#ConceptScheme) referred by  uri &lt;concept schema uri&gt; is not found,
     * @apiErrorExample ValidationError
     *   HTTP/1.1 400 Bad request
     *   The resource (of type http://www.w3.org/2004/02/skos/core#ConceptScheme) referred by  uri &lt;concept schema uri&gt; is not found,
     */
    public function putAction()
    {
      $this->throw501 =false; 
      parent::putAction();
    }

    /**
     *
     * @apiVersion 1.0.0
     * @apiDescription Delete a SKOS Concept
     * @api {delete} /api/concept Delete SKOS concept
     * @apiName DeleteConcept
     * @apiGroup Concept
     * @apiParam {String} tenant The institute code for your institute in the OpenSKOS portal
     * @apiParam {String} key A valid API key
     * @apiParam {String} id The uri of the concept
     * @apiSuccess (202) {xml} Body
     * @apiSuccessExample Success-Response:
     *   HTTP/1.1 202 Accepted
     *   &lt;?xml version="1.0"?>
     *   &lt;rdf:RDF xmlns:dc="http://purl.org/dc/elements/1.1/"
     *      xmlns:dcterms="http://purl.org/dc/terms/"
     *      xmlns:openskos="http://openskos.org/xmlns#"
     *      xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
     *      xmlns:skos="http://www.w3.org/2004/02/skos/core#">
     *     &lt;rdf:Description rdf:about="http://data.beeldengeluid.nl/gtaa/285863243243224">
     *           &lt;rdf:type rdf:resource="http://www.w3.org/2004/02/skos/core#Concept"/>
     *           &lt;skos:prefLabel xml:lang="nl">doodstraff</skos:prefLabel>
     *           &lt;skos:inScheme rdf:resource="http://data.beeldengeluid.nl/gtaa/Onderwerpen"/>
     *           &lt;skos:broader rdf:resource="http://data.beeldengeluid.nl/gtaa/24842"/>
     *           &lt;skos:related rdf:resource="http://data.beeldengeluid.nl/gtaa/25652"/>
     *           &lt;skos:related rdf:resource="http://data.beeldengeluid.nl/gtaa/24957"/>
     *           &lt;skos:altLabel xml:lang="nl">kruisigingen&lt;/skos:altLabel>
     *           &lt;skos:broader rdf:resource="http://data.beeldengeluid.nl/gtaa/27731"/>
     *           &lt;skos:related rdf:resource="http://data.beeldengeluid.nl/gtaa/28109"/>
     *           &lt;skos:inScheme rdf:resource="http://data.beeldengeluid.nl/gtaa/GTAA"/>
     *           &lt;skos:notation>285863243243224&lt;/skos:notation>
     *           &lt;openskos:uuid>GTAA_-2571_b6e99d21-fdd8-5071-5179-1a2876cda0e8 &lt;openskos:uuid>
     *           &lt;dcterms:dateSubmitted rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2017-02-24T14:19:57+00:00&lt;/dcterms:dateSubmitted>
     *           &lt;dcterms:creator rdf:resource="http://localhost/clavas/public/api/users/f122deab-755a-4f67-8502-7cd9bfd70ec5"/>
     *           &lt;dcterms:modified rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2017-02-24T14:33:45+00:00&lt;/dcterms:modified>
     *           &lt;dcterms:contributor rdf:resource="http://localhost/clavas/public/api/users/f122deab-755a-4f67-8502-7cd9bfd70ec5"/>
     *           &lt;openskos:status>deleted&lt;/openskos:status>
     *           &lt;openskos:dateDeleted rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2016-11-12T04:13:45+00:00&lt;/openskos:dateDeleted>
     *     &lt;/rdf:Description>
     *   &lt;/rdf:RDF>
     * 
     * @apiError Gone Concept already deleted: &lt;concept uri&gt;
     * @apiErrorExample Gone
     *   HTTP/1.1 410 Gone
     *   Concept already deleted: &lt;concept uri&gt;
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
     */
    public function deleteAction()
    {
      $this->throw501=false;
      parent::deleteAction();
    }
    
    

}
