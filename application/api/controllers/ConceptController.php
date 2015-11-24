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
    * Add the following XML to the body of the request
    *
    * <pre class="prettyprint language-xml prettyprinted">
    * &lt;rdf:RDF
    *    xmlns:rdf=&quot;http://www.w3.org/1999/02/22-rdf-syntax-ns#&quot;
    *    xmlns:openskos=&quot;http://openskos.org/xmlns#&quot;
    *    xmlns:skos=&quot;http://www.w3.org/2004/02/skos/core#&quot;
    *    openskos:tenant=&quot;beg&quot; openskos:collection=&quot;gtaa&quot; openskos:key=&quot;your-api-key&quot;&gt;
    *    &lt;rdf:Description rdf:about=&quot;http://data.beeldengeluid.nl/gtaa/28586&quot;&gt;
    *      &lt;rdf:type rdf:resource=&quot;http://www.w3.org/2004/02/skos/core#Concept&quot;/&gt;
    *      &lt;skos:prefLabel xml:lang=&quot;nl&quot;&gt;doodstraf&lt;/skos:prefLabel&gt;
    *      &lt;skos:inScheme rdf:resource=&quot;http://data.beeldengeluid.nl/gtaa/Onderwerpen&quot;/&gt;
    *      &lt;skos:broader rdf:resource=&quot;http://data.beeldengeluid.nl/gtaa/24842&quot;/&gt;
    *      &lt;skos:related rdf:resource=&quot;http://data.beeldengeluid.nl/gtaa/25652&quot;/&gt;
    *      &lt;skos:related rdf:resource=&quot;http://data.beeldengeluid.nl/gtaa/24957&quot;/&gt;
    *      &lt;skos:altLabel xml:lang=&quot;nl&quot;&gt;kruisigingen&lt;/skos:altLabel&gt;
    *      &lt;skos:broader rdf:resource=&quot;http://data.beeldengeluid.nl/gtaa/27731&quot;/&gt;
    *      &lt;skos:related rdf:resource=&quot;http://data.beeldengeluid.nl/gtaa/28109&quot;/&gt;
    *      &lt;skos:inScheme rdf:resource=&quot;http://data.beeldengeluid.nl/gtaa/GTAA&quot;/&gt;
    *      &lt;skos:notation&gt;28586&lt;/skos:notation&gt;
    *    &lt;/rdf:Description&gt;
    *  &lt;/rdf:RDF&gt;
    * </pre>
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
    *   <?xml version="1.0"?>
    *   <rdf:RDF xmlns:dc="http://purl.org/dc/elements/1.1/"
     *      xmlns:dcterms="http://purl.org/dc/terms/"
     *      xmlns:openskos="http://openskos.org/xmlns#"
     *      xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
     *      xmlns:skos="http://www.w3.org/2004/02/skos/core#">
    *   <rdf:Description rdf:about="http://data.beeldengeluid.nl/gtaa/285863243243224">
    *           <rdf:type rdf:resource="http://www.w3.org/2004/02/skos/core#Concept"/>
    *           <skos:prefLabel xml:lang="nl">doodstraff</skos:prefLabel>
    *           <skos:inScheme rdf:resource="http://data.beeldengeluid.nl/gtaa/Onderwerpen"/>
    *           <skos:broader rdf:resource="http://data.beeldengeluid.nl/gtaa/24842"/>
    *           <skos:related rdf:resource="http://data.beeldengeluid.nl/gtaa/25652"/>
    *           <skos:related rdf:resource="http://data.beeldengeluid.nl/gtaa/24957"/>
    *           <skos:altLabel xml:lang="nl">kruisigingen</skos:altLabel>
    *           <skos:broader rdf:resource="http://data.beeldengeluid.nl/gtaa/27731"/>
    *           <skos:related rdf:resource="http://data.beeldengeluid.nl/gtaa/28109"/>
    *           <skos:inScheme rdf:resource="http://data.beeldengeluid.nl/gtaa/GTAA"/>
    *           <skos:notation>285863243243224</skos:notation>
    *         <openskos:status>candidate</openskos:status>
    *   </rdf:Description>
    *   </rdf:RDF>
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
    *  Concept `uri` already exists
    * @apiError UniquePreflabel {String} The concept preflabel must be unique per scheme
    * @apiErrorExample UniquePreflabel:
    *   HTTP/1.1 400 Bad request
    *   The concept preflabel must be unique per scheme
    */
    public function postAction()
    {
        $request = $this->getPsrRequest();
        $api = new OpenSkos2\Api\Concept($this->getConceptManager());
        $response = $api->create($request);
        $this->emitResponse($response);
    }
    
    /**
    *
    * @apiVersion 1.0.0
    * @apiDescription Update a SKOS Concept
    * Add the following XML to the body of the request
    *
    * <pre class="prettyprint language-xml prettyprinted">
    * &lt;rdf:RDF
    *    xmlns:rdf=&quot;http://www.w3.org/1999/02/22-rdf-syntax-ns#&quot;
    *    xmlns:openskos=&quot;http://openskos.org/xmlns#&quot;
    *    xmlns:skos=&quot;http://www.w3.org/2004/02/skos/core#&quot;
    *    openskos:tenant=&quot;beg&quot; openskos:collection=&quot;gtaa&quot; openskos:key=&quot;your-api-key&quot;&gt;
    *    &lt;rdf:Description rdf:about=&quot;http://data.beeldengeluid.nl/gtaa/28586&quot;&gt;
    *      &lt;rdf:type rdf:resource=&quot;http://www.w3.org/2004/02/skos/core#Concept&quot;/&gt;
    *      &lt;skos:prefLabel xml:lang=&quot;nl&quot;&gt;doodstraf&lt;/skos:prefLabel&gt;
    *      &lt;skos:inScheme rdf:resource=&quot;http://data.beeldengeluid.nl/gtaa/Onderwerpen&quot;/&gt;
    *      &lt;skos:broader rdf:resource=&quot;http://data.beeldengeluid.nl/gtaa/24842&quot;/&gt;
    *      &lt;skos:related rdf:resource=&quot;http://data.beeldengeluid.nl/gtaa/25652&quot;/&gt;
    *      &lt;skos:related rdf:resource=&quot;http://data.beeldengeluid.nl/gtaa/24957&quot;/&gt;
    *      &lt;skos:altLabel xml:lang=&quot;nl&quot;&gt;kruisigingen&lt;/skos:altLabel&gt;
    *      &lt;skos:broader rdf:resource=&quot;http://data.beeldengeluid.nl/gtaa/27731&quot;/&gt;
    *      &lt;skos:related rdf:resource=&quot;http://data.beeldengeluid.nl/gtaa/28109&quot;/&gt;
    *      &lt;skos:inScheme rdf:resource=&quot;http://data.beeldengeluid.nl/gtaa/GTAA&quot;/&gt;
    *      &lt;skos:notation&gt;28586&lt;/skos:notation&gt;
    *    &lt;/rdf:Description&gt;
    *  &lt;/rdf:RDF&gt;
    * </pre>
    *
    * @api {put} /api/concept Update SKOS concept
    * @apiName UpdateConcept
    * @apiGroup Concept
    *
    * @apiParam {String} tenant The institute code for your institute in the OpenSKOS portal
    * @apiParam {String} collection The collection code for the collection the concept must be put in
    * @apiParam {String} key A valid API key
    * @apiSuccess (201) {String} Concept uri
    * @apiSuccessExample {String} Success-Response
    *   HTTP/1.1 200 Ok
    *   <?xml version="1.0"?>
    *   <rdf:RDF xmlns:dc="http://purl.org/dc/elements/1.1/"
     *      xmlns:dcterms="http://purl.org/dc/terms/"
     *      xmlns:openskos="http://openskos.org/xmlns#"
     *      xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
     *      xmlns:skos="http://www.w3.org/2004/02/skos/core#">
    *   <rdf:Description rdf:about="http://data.beeldengeluid.nl/gtaa/285863243243224">
    *           <rdf:type rdf:resource="http://www.w3.org/2004/02/skos/core#Concept"/>
    *           <skos:prefLabel xml:lang="nl">doodstraff</skos:prefLabel>
    *           <skos:inScheme rdf:resource="http://data.beeldengeluid.nl/gtaa/Onderwerpen"/>
    *           <skos:broader rdf:resource="http://data.beeldengeluid.nl/gtaa/24842"/>
    *           <skos:related rdf:resource="http://data.beeldengeluid.nl/gtaa/25652"/>
    *           <skos:related rdf:resource="http://data.beeldengeluid.nl/gtaa/24957"/>
    *           <skos:altLabel xml:lang="nl">kruisigingen</skos:altLabel>
    *           <skos:broader rdf:resource="http://data.beeldengeluid.nl/gtaa/27731"/>
    *           <skos:related rdf:resource="http://data.beeldengeluid.nl/gtaa/28109"/>
    *           <skos:inScheme rdf:resource="http://data.beeldengeluid.nl/gtaa/GTAA"/>
    *           <skos:notation>285863243243224</skos:notation>
    *         <openskos:status>candidate</openskos:status>
    *   </rdf:Description>
    *   </rdf:RDF>
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
    *  Concept `uri` already exists
    * @apiError UniquePreflabel {String} The concept preflabel must be unique per scheme
    * @apiErrorExample UniquePreflabel:
    *   HTTP/1.1 400 Bad request
    *   The concept preflabel must be unique per scheme
    */    
    public function putAction()
    {
        $request = $this->getPsrRequest();
        $api = new OpenSkos2\Api\Concept($this->getConceptManager());
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
    *   <?xml version="1.0"?>
    *   <rdf:RDF xmlns:dc="http://purl.org/dc/elements/1.1/"
    *      xmlns:dcterms="http://purl.org/dc/terms/"
    *      xmlns:openskos="http://openskos.org/xmlns#"
    *      xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
    *      xmlns:skos="http://www.w3.org/2004/02/skos/core#">
    *   <rdf:Description rdf:about="http://data.beeldengeluid.nl/gtaa/285863243243224">
    *           <rdf:type rdf:resource="http://www.w3.org/2004/02/skos/core#Concept"/>
    *           <skos:prefLabel xml:lang="nl">doodstraff</skos:prefLabel>
    *           <skos:inScheme rdf:resource="http://data.beeldengeluid.nl/gtaa/Onderwerpen"/>
    *           <skos:broader rdf:resource="http://data.beeldengeluid.nl/gtaa/24842"/>
    *           <skos:related rdf:resource="http://data.beeldengeluid.nl/gtaa/25652"/>
    *           <skos:related rdf:resource="http://data.beeldengeluid.nl/gtaa/24957"/>
    *           <skos:altLabel xml:lang="nl">kruisigingen</skos:altLabel>
    *           <skos:broader rdf:resource="http://data.beeldengeluid.nl/gtaa/27731"/>
    *           <skos:related rdf:resource="http://data.beeldengeluid.nl/gtaa/28109"/>
    *           <skos:inScheme rdf:resource="http://data.beeldengeluid.nl/gtaa/GTAA"/>
    *           <skos:notation>285863243243224</skos:notation>
    *         <openskos:status>candidate</openskos:status>
    *   </rdf:Description>
    *   </rdf:RDF>
    * @apiSuccessExample {String} Success-Response
    *   HTTP/1.1 202 Deleted
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
    */
    public function deleteAction()
    {
        $request = $this->getPsrRequest();
        $api = new OpenSkos2\Api\Concept($this->getConceptManager());
        $response = $api->delete($request);
        $this->emitResponse($response);
    }
}
