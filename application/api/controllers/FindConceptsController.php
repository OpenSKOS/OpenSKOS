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


class Api_FindConceptsController extends AbstractController {

    public function init()
    {
       parent::init();
       $this->fullNameResourceClass = 'OpenSkos2\Api\Concept';
       $this ->viewpath="concept/";
       
        if ('html' == $this->_helper->contextSwitch()->getCurrentContext()) {
            //enable layout:
            $this->getHelper('layout')->enableLayout();
        }
    }

    /**
     * @apiVersion 1.0.0
     * @apiDescription Find a SKOS Concept
     * The following requests are possible
     *
     * /api/find-concepts?q=doood
     *
     * /api/find-concepts?q=do*
     *
     * /api/find-concepts?q=prefLabel:dood
     *
     * /api/find-concepts?q=do* status:approved
     *
     * /api/find-concepts?q=prefLabel:do*&rows=0
     *
     * /api/find-concepts?q=prefLabel@nl:doo
     *
     * /api/find-concepts?q=prefLabel@nl:do*
     *
     * /api/find-concepts?q=do*&tenant=beng&collection=gtaa
     *
     * /api/find-concepts?q=do*&scheme=http://data.cultureelerfgoed.nl/semnet/objecten
     *
     * @api {get} /api/find-concepts Find a concept
     * @apiName FindConcepts
     * @apiGroup FindConcept
     * @apiParam {String} q search term
     * @apiParam {String} rows Number of rows to return
     * @apiParam {String} fl List of fields to return
     * @apiParam {String} tenant Name of the tenant to query. Default is all tenants
     * @apiParam {String} collection OpenSKOS set to query. Default is all sets
     * @apiParam {String} scheme id of the SKOS concept scheme to query. Default is all schemes
     * @apiSuccess (200) {String} XML
     * @apiSuccessExample {String} Success-Response
     *   HTTP/1.1 200 Ok
     *   &lt;?xml version="1.0"?>
     *      &lt;rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
     *          xmlns:skos="http://www.w3.org/2004/02/skos/core#"
     *          xmlns:dc="http://purl.org/dc/elements/1.1/"
     *          xmlns:dcterms="http://purl.org/dc/terms/"
     *          xmlns:openskos="http://openskos.org/xmlns#"
     *          xmlns:owl="http://www.w3.org/2002/07/owl#"
     *          xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#"
     *          openskos:numFound="15"
     *          openskos:start="0">
     *   &lt;rdf:Description xmlns:dc="http://purl.org/dc/terms/"
     *      rdf:about="http://data.cultureelerfgoed.nl/semnet/efc584d7-9880-43fb-9a0b-76f3036aa315">
     *      &lt;rdf:type rdf:resource="http://www.w3.org/2004/02/skos/core#Concept"/>
     *         &lt;skos:prefLabel xml:lang="nl">doodshemden&lt;/skos:prefLabel>
     *         &lt;skos:altLabel xml:lang="nl">doodshemd&lt;/skos:altLabel>
     *         &lt;openskos:tenant>rce&lt;/openskos:tenant>
     *         &lt;skos:notation>1183132&lt;/skos:notation>
     *         &lt;skos:inScheme rdf:resource="http://data.cultureelerfgoed.nl/semnet/erfgoedthesaurus"/>
     *         &lt;skos:inScheme rdf:resource="http://data.cultureelerfgoed.nl/semnet/objecten"/>
     *         &lt;openskos:uuid>945bb5a9-0277-9df4-d206-a129bc144da4&lt;/openskos:uuid>
     *         &lt;skos:related rdf:resource="http://data.cultureelerfgoed.nl/semnet/77f6ff1b-b603-4a76-a264-10b3f25eb7df"/>
     *         &lt;dc:modified rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2015-07-03T09:30:05+00:00&lt;/dc:modified>
     *         &lt;skos:definition xml:lang="nl">Albevormig hemd waarin een dode wordt gekleed.&lt;/skos:definition>
     *         &lt;skos:broader rdf:resource="http://data.cultureelerfgoed.nl/semnet/7deba87b-1ac5-450f-bff7-78865d3b4742"/>
     *         &lt;dc:dateSubmitted rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2015-07-03T09:27:56+00:00&lt;/dc:dateSubmitted>
     *         &lt;openskos:dateDeleted rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2015-10-09T09:33:06+00:00&lt;/openskos:dateDeleted>
     *         &lt;openskos:status>deleted&lt;/openskos:status>
     *         &lt;openskos:collection rdf:resource="http://openskos.org/api/collections/rce:EGT"/>
     *     &lt;/rdf:Description>
     *   &lt;/rdf:RDF>
     *
     */
    public function indexAction()
    {   
        $format = $this->getRequest()->getParam('format');
        if ($format === 'html') {
            $this->getHelper('layout')->enableLayout();
            return $this->renderScript('concept/index.phtml');
        } else {
            if (null === ($q = $this->getRequest()->getParam('q'))) {
                
                $this->getResponse()
                        ->setHeader('X-Error-Msg', 'Missing required parameter `q`');
                throw new Zend_Controller_Exception('Missing required parameter `q`', 400);
            }

            $this->getHelper('layout')->disableLayout();
            $this->_helper->viewRenderer->setNoRender(true);

            $concept = $this->getDI()->make('OpenSkos2\Api\Concept');
           
            $context = $this->_helper->contextSwitch()->getCurrentContext();
            $request = $this->getPsrRequest();
            $response = $concept->findConcepts($request, $context);
            $this->emitResponse($response);
        }
    }

    /**
     * @apiVersion 1.0.0
     * @apiDescription Return a specific concept
     * The following requests are valid
     *
     * /api/concept/1b345c95-7256-4bb2-86f6-7c9949bd37ac.rdf (rdf format)
     *
     * /api/concept/1b345c95-7256-4bb2-86f6-7c9949bd37ac.html (html format)
     *
     * /api/concept/1b345c95-7256-4bb2-86f6-7c9949bd37ac.json (json format)
     *
     * /api/concept/82c2614c-3859-ed11-4e55-e993c06fd9fe.jsonp&callback=test (jsonp format)
     *
     * /api/concept/?id=http://example.com/1 (rdf format)
     *
     * @api {get} /api/concept/{id}.rdf Get concept detail
     * @apiName GetConcept
     * @apiGroup FindConcept
     * @apiParam {String} fl List of fields to return
     * @apiSuccess (200) {String} XML
     * @apiSuccessExample {String} Success-Response
     *   HTTP/1.1 200 Ok
     *   &lt;?xml version="1.0" encoding="utf-8" ?>
     *   &lt;rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
     *           xmlns:skos="http://www.w3.org/2004/02/skos/core#"
     *           xmlns:dc="http://purl.org/dc/terms/"
     *           xmlns:dcterms="http://purl.org/dc/elements/1.1/"
     *           xmlns:openskos="http://openskos.org/xmlns#">
     *

     *   &lt;rdf:Description rdf:about="http://data.beeldengeluid.nl/gtaa/218059">
     *       &lt;rdf:type rdf:resource="http://www.w3.org/2004/02/skos/core#Concept"/>
     *       &lt;skos:historyNote xml:lang="nl">Recordnummer: 11665
     *   Datum invoer: 13-12-1998
     *   Gebruiker invoer: SEBASTIAAN
     *   Datum gewijzigd: 12-10-2004
     *   Gebruiker gewijzigd: Beng&lt;/skos:historyNote>
     *       &lt;skos:historyNote xml:lang="nl">Goedgekeurd door: Alma Wolthuis&lt;/skos:historyNote>
     *       &lt;skos:historyNote xml:lang="nl">Gewijzigd door: Alma Wolthuis&lt;/skos:historyNote>
     *       &lt;skos:broader rdf:resource="http://data.beeldengeluid.nl/gtaa/217190"/>
     *       &lt;skos:related rdf:resource="http://data.beeldengeluid.nl/gtaa/215665"/>
     *       &lt;skos:related rdf:resource="http://data.beeldengeluid.nl/gtaa/216387"/>
     *       &lt;skos:related rdf:resource="http://data.beeldengeluid.nl/gtaa/217572"/>
     *       &lt;dcterms:creator rdf:resource="http://openskos.org/users/9f598c22-1fd4-4113-9447-7c71d0c7146f"/>
     *       &lt;skos:broadMatch rdf:resource="http://data.beeldengeluid.nl/gtaa/24842"/>
     *       &lt;openskos:collection rdf:resource="http://openskos.org/api/collections/beg:gtaa"/>
     *       &lt;openskos:status>approved&lt;/openskos:status>
     *       &lt;skos:prefLabel xml:lang="nl">doodstraf&lt;/skos:prefLabel>
     *       &lt;skos:altLabel xml:lang="nl">kruisigingen&lt;/skos:altLabel>
     *       &lt;openskos:tenant>beg&lt;/openskos:tenant>
     *       &lt;dc:contributor>RVD, SFW, NFM, GWA, TVA&lt;/dc:contributor>
     *       &lt;skos:notation>218059&lt;/skos:notation>
     *       &lt;skos:inScheme rdf:resource="http://data.beeldengeluid.nl/gtaa/OnderwerpenBenG"/>
     *       &lt;dcterms:modified rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2009-11-30T17:30:51+00:00&lt;/dcterms:modified>
     *       &lt;dcterms:dateSubmitted rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2009-11-30T15:03:48+00:00&lt;/dcterms:dateSubmitted>
     *       &lt;dcterms:dateAccepted rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2009-11-30T15:03:48+00:00&lt;/ddcterms:dateAccepted>
     *       &lt;openskos:uuid>03ae64e0-94ba-55d8-c01a-6f4259e95177&lt;/openskos:uuid>
     *     &lt;/rdf:Description>
     *   &lt;/rdf:RDF>
     *
     */
    public function getAction()
    {
         parent::getAction();
    }

    public function postAction()
    {
        $this->_501('POST');
    }

    public function putAction()
    {
        $this->_501('PUT');
    }

    public function deleteAction()
    {
        $this->_501('DELETE');
    }
    
// TODO: ask Picturae when do they need that id prefix: why ID prefix is not a part of ID
    // also, it is a bit strange to use id generation in get function
    /**
     * Get concept id
     *
     * @throws Zend_Controller_Exception
     * @return string|\OpenSkos2\Rdf\Uri
     */
    
    private function getId()
    {
        $id = $this->getRequest()->getParam('id');
        if (null === $id) {
            throw new Zend_Controller_Exception('No id `' . $id . '` provided', 400);
        }

        if (strpos($id, 'http://') !== false || strpos($id, 'https://') !== false) {
            return new OpenSkos2\Rdf\Uri($id);
        }

        /*
         * this is for clients that need special routes like "http://data.beeldenegluid.nl/gtaa/123456"
         * with this we can create a route in the config ini like this:
         *
         * resources.router.routes.route_id.type = "Zend_Controller_Router_Route_Regex"
         * resources.router.routes.route_id.route = "gtaa\/(\d+)"
         * resources.router.routes.route_id.defaults.module = "api"
         * resources.router.routes.route_id.defaults.controller = "concept"
         * resources.router.routes.route_id.defaults.action = "get"
         * resources.router.routes.route_id.defaults.id_prefix = "http://data.beeldengeluid.nl/gtaa/"
         * resources.router.routes.route_id.defaults.format = "html"
         * resources.router.routes.route_id.map.1 = "id"
         * resources.router.routes.route_id.reverse = "gtaa/%d"
         */

        $id_prefix = $this->getRequest()->getParam('id_prefix');
        if (null !== $id_prefix && \Rhumsaa\Uuid\Uuid::isValid($id)) {
            $id_prefix = str_replace('%tenant%', $this->getRequest()->getParam('tenant'), $id_prefix);
            $id = $id_prefix . $id;
        }

        return $id;
    }
    
}
