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
use OpenSkos2\Namespaces\Skos;

require_once 'FindConceptsController.php';

class Api_ConceptController extends Api_FindConceptsController
{

    public function init()
    {
        $this->getHelper('layout')->disableLayout();
        $this->getHelper('viewRenderer')->setNoRender(true);
        parent::init();
    }

    /**
     *
     * @apiVersion 1.0.0
     * @apiDescription Create a SKOS Concept
     *
     * Create a new SKOS concept based on the post data
     *
     * @apiExample {String} Example request
     * <rdf:RDF
     *    xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
     *    xmlns:openskos="http://openskos.org/xmlns#"
     *    xmlns:skos="http://www.w3.org/2004/02/skos/core#"
     *    openskos:tenant="beg" openskos:collection="gtaa" openskos:key="your-api-key">
     *    <rdf:Description rdf:about="http://data.beeldengeluid.nl/gtaa/28586">
     *      <rdf:type rdf:resource="http://www.w3.org/2004/02/skos/core#Concept">
     *      <skos:prefLabel xml:lang="nl">doodstraf</skos:prefLabel>
     *      <skos:inScheme rdf:resource="http://data.beeldengeluid.nl/gtaa/Onderwerpen">
     *      <skos:broader rdf:resource="http://data.beeldengeluid.nl/gtaa/24842">
     *      <skos:related rdf:resource="http://data.beeldengeluid.nl/gtaa/25652">
     *      <skos:related rdf:resource="http://data.beeldengeluid.nl/gtaa/24957">
     *      <skos:altLabel xml:lang="nl">kruisigingen</skos:altLabel>
     *      <skos:broader rdf:resource="http://data.beeldengeluid.nl/gtaa/27731">
     *      <skos:related rdf:resource="http://data.beeldengeluid.nl/gtaa/28109">
     *      <skos:inScheme rdf:resource="http://data.beeldengeluid.nl/gtaa/GTAA">
     *      <skos:notation>28586</skos:notation>
     *    </rdf:Description>
     *  </rdf:RDF>
     *
     * @api {post} /api/concept Create SKOS concept
     * @apiName CreateConcept
     * @apiGroup Concept
     *
     * @apiParam {String} tenant The institute code for your institute in the OpenSKOS portal
     * @apiParam {String} collection The collection code for the collection the concept must be put in
     * @apiParam {String} key A valid API key
     * @apiParam {String="true","false","1","0"} autoGenerateIdentifiers If set to true (any of "1", "true", "on" and "yes") the concept uri (rdf:about) will be automatically generated.
     *                                           If uri exists in the xml and autoGenerateIdentifiers is true - an error will be thrown.
     *                                           If set to false - the xml must contain uri (rdf:about).
     * @apiSuccess (201) {String} Concept uri
     * @apiSuccessExample {String} Success-Response
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
     *   &lt;/rdf:Description>
     *   &lt;/rdf:RDF>
     * @apiError MissingKey {String} No key specified
     * @apiErrorExample MissingKey:
     *   HTTP/1.1 412 Precondition Failed
     *   No key specified
     * @apiError MissingTenant {String} No tenant specified
     * @apiErrorExample MissingTenant:
     *   HTTP/1.1 412 Precondition Failed
     *   No tenant specified
     * @apiError MissingCollection {String} No collection specified
     * @apiErrorExample MissingCollection:
     *   HTTP/1.1 412 Precondition Failed
     *   No collection specified
     * @apiError ConceptExists {String} Concept `uri` already exists
     * @apiErrorExample ConceptExists:
     *   HTTP/1.1 409 Not Found
     *   Concept `uri` already exists
     * @apiError UniquePreflabel {String} The concept preflabel must be unique per scheme
     * @apiErrorExample UniquePreflabel:
     *   HTTP/1.1 400 Bad request
     *   The concept preflabel must be unique per scheme
     */
    public function postAction()
    {
        $request = $this->getPsrRequest();
        $api = $this->getDI()->make('\OpenSkos2\Api\Concept');
        $response = $api->create($request);
        $this->emitResponse($response);
    }

    /**
     *
     * @apiVersion 1.0.0
     * @apiDescription Update a SKOS Concept
     *
     * Update the specified concept with the new data present in the request.
     *
     * @apiExample {String} Example request
     *  <rdf:RDF
     *    xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
     *    xmlns:openskos="http://openskos.org/xmlns#"
     *    xmlns:skos="http://www.w3.org/2004/02/skos/core#"
     *    openskos:tenant="beg" openskos:collection="gtaa" openskos:key="your-api-key">
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
     *    </rdf:Description>
     *  </rdf:RDF>
     *
     * @apiName UpdateConcept
     * @apiGroup Concept
     *
     * @api {put} /api/concept Update SKOS concept
     * @apiParam {String} tenant The institute code for your institute in the OpenSKOS portal
     * @apiParam {String} collection The collection code for the collection the concept must be put in
     * @apiParam {String} key A valid API key
     * @apiSuccess (201) {String} Concept uri
     * @apiSuccessExample {String} Success-Response
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
     *   &lt;/rdf:Description>
     *   &lt;/rdf:RDF>
     * @apiError MissingKey {String} No key specified
     * @apiErrorExample MissingKey
     *   HTTP/1.1 412 Precondition Failed
     *   No key specified
     * @apiError MissingTenant {String} No tenant specified
     * @apiErrorExample MissingTenant
     *   HTTP/1.1 412 Precondition Failed
     *   No tenant specified
     * @apiError MissingCollection {String} No collection specified
     * @apiErrorExample MissingCollection
     *   HTTP/1.1 412 Precondition Failed
     *   No collection specified
     * @apiError ConceptExists {String} Concept `uri` already exists
     * @apiErrorExample ConceptExists
     *   HTTP/1.1 409 Not Found
     *  Concept `uri` already exists
     * @apiError UniquePreflabel {String} The concept preflabel must be unique per scheme
     * @apiErrorExample UniquePreflabel
     *   HTTP/1.1 400 Bad request
     *   The concept preflabel must be unique per scheme
     */
    public function putAction()
    {
        $request = $this->getPsrRequest();
        $api = $this->getDI()->make('\OpenSkos2\Api\Concept');
        $response = $api->update($request);
        $this->emitResponse($response);
    }

    /**
     *
     * @apiVersion 1.0.0
     * @apiDescription Delete a SKOS Concept
     * @api {delete} /api/concept Delete SKOS concept
     * @apiName DeleteConcept
     * @apiGroup Concept
     * @apiParam {String} tenant The institute code for your institute in the OpenSKOS portal
     * @apiParam {String} collection The collection code for the collection the concept must be put in
     * @apiParam {String} key A valid API key
     * @apiParam {String} id The uri of the concept
     * @apiSuccess (202) {String} Concept uri
     * @apiSuccessExample {String} Success-Response
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
     *           &lt;openskos:status>deleted&lt;/openskos:status>
     *           &lt;openskos:dateDeleted rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2016-11-12T04:13:45+00:00&lt;/openskos:dateDeleted>
     *     &lt;/rdf:Description>
     *   &lt;/rdf:RDF>
     * @apiError Gone {String} Concept already deleted :http://data.beeldengeluid.nl/gtaa/285863243243224
     * @apiErrorExample Gone
     *   HTTP/1.1 410 Gone
     *   Concept already deleted :http://data.beeldengeluid.nl/gtaa/285863243243224
     * @apiError MissingKey {String} No key specified
     * @apiErrorExample MissingKey
     *   HTTP/1.1 412 Precondition Failed
     *   No key specified
     * @apiError MissingTenant {String} No tenant specified
     * @apiErrorExample MissingTenant
     *   HTTP/1.1 412 Precondition Failed
     *   No tenant specified
     * @apiError MissingCollection {String} No collection specified
     * @apiErrorExample MissingCollection
     *   HTTP/1.1 412 Precondition Failed
     *   No collection specified
     */
    public function deleteAction()
    {
        $request = $this->getPsrRequest();
        $api = $this->getDI()->make('\OpenSkos2\Api\Concept');
        $response = $api->delete($request, Skos::CONCEPT);
        $this->emitResponse($response);
    }
}
