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
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@zend.com so we can send you a copy immediately.
*
* @category   OpenSKOS
* @package    OpenSKOS
* @copyright  Copyright (c) 2011 Pictura Database Publishing. (http://www.pictura-dp.nl)
* @author     Mark Lindeman
* @license    http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
*/

class OaiPmh_IndexController extends OpenSKOS_Rest_Controller
{
    public function init()
    {
        $this->getHelper('layout')->disableLayout();
        $this->getResponse()->setHeader('Content-Type', 'text/xml; charset=utf8');
    }

    /**
     * @apiVersion 1.0.0
     * @apiDescription Test OAI-PHM doc
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
     * Skos-XL labels can be fetched instead of simple labels for each of the valid requests by specifying the xl and tenant parameters
     * 
     * /api/find-concepts?q=do*&xl=1&tenant=pic
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
        $this->_helper->viewRenderer->setNoRender(true);

        $repository = new OpenSkos2\OaiPmh\Repository(
            $this->getDI()->get('OpenSkos2\ConceptManager'),
            $this->getDI()->get('OpenSkos2\ConceptSchemeManager'),
            $this->getDI()->get('OpenSkos2\Search\Autocomplete'),
            'OpenSKOS - OAI-PMH Service provider',
            $this->getBaseUrl(),
            ['oai-pmh@openskos.org'],
            new \OpenSKOS_Db_Table_Collections(),
            null
        );
        
        $request = Zend\Diactoros\ServerRequestFactory::fromGlobals();
        $provider = new Picturae\OaiPmh\Provider($repository, $request);
        $response = $provider->getResponse();
        (new Zend\Diactoros\Response\SapiEmitter())->emit($response);
    }

    public function getAction()
    {
        $this->_501('GET');
    }

    public function postAction()
    {
        $this->_501('POST');
    }

    public function putAction()
    {
        $this->_501('POST');
    }

    public function deleteAction()
    {
        $this->_501('DELETE');
    }

    /**
     * Get base url
     * @return string
     */
    private function getBaseUrl()
    {
        return $this->view->serverUrl() . $this->view->url();
    }
}
