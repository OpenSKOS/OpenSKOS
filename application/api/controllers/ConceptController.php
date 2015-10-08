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
    * @apiParam {String="true","false","1","0"} autoGenerateIdentifiers If set to true (any of "1", "true", "on" and "yes") the concept notation and uri (rdf:about) will be automatically generated.
    *                                           If notation and/or uri exists in the xml and autoGenerateIdentifiers is true - an error will be thrown.
    *                                           If set to false - the xml must contain both notation and uri (rdf:about). The uri must be based on notation (must contain the notation).
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
    * @apiError WrongNotation {String} The concept uri (rdf:about) must be based on notation (must contain the notation)
    * @apiErrorExample WrongNotation:
    *   HTTP/1.1 400 Bad request
    *   The concept uri (rdf:about) must be based on notation (must contain the notation)
    * @apiError UniquePreflabel {String} The concept preflabel must be unique per scheme
    * @apiErrorExample UniquePreflabel:
    *   HTTP/1.1 400 Bad request
    *   The concept preflabel must be unique per scheme
    */
    public function postAction()
    {
        $this->getHelper('layout')->disableLayout();
        $this->getHelper('viewRenderer')->setNoRender(true);

        $request = \Zend\Diactoros\ServerRequestFactory::fromGlobals();
        $xml = $this->getRequest()->getRawBody();
        $api = new OpenSkos2\Api\Concept($this->getConceptManager());
        $response = $api->create($request, $xml);
        (new \Zend\Diactoros\Response\SapiEmitter())->emit($response);
        exit; // find better way to prevent output from zf1
    }

    /**
     * Old method
     * @throws Zend_Controller_Action_Exception
     */
    public function postTestAction()
    {
        $this->getHelper('layout')->disableLayout();
        $this->getHelper('viewRenderer')->setNoRender(true);
        $this->view->errorOnly = true;

        $xml = $this->getRequest()->getRawBody();
        if (!$xml) {
            throw new Zend_Controller_Action_Exception('No RDF-XML recieved', 412);
        }

        $doc = new DOMDocument();
        if (!@$doc->loadXML($xml)) {
            throw new Zend_Controller_Action_Exception('Recieved RDF-XML is not valid XML', 412);
        }

        //do some basic tests
        if ($doc->documentElement->nodeName != 'rdf:RDF') {
            throw new Zend_Controller_Action_Exception('Recieved RDF-XML is not valid: expected <rdf:RDF/> rootnode, got <'.$doc->documentElement->nodeName.'/>', 412);
        }

        $Descriptions = $doc->documentElement->getElementsByTagNameNs(OpenSKOS_Rdf_Parser::$namespaces['rdf'], 'Description');
        if ($Descriptions->length != 1) {
            throw new Zend_Controller_Action_Exception('Expected exactly one /rdf:RDF/rdf:Description, got '.$Descriptions->length, 412);
        }

        //is a tenant, collection or api key set in the XML?
        foreach (array('tenant', 'collection', 'key') as $attributeName) {
            $value = $doc->documentElement->getAttributeNS(OpenSKOS_Rdf_Parser::$namespaces['openskos'], $attributeName);
            if ($value) {
                $this->getRequest()->setParam($attributeName, $value);
            }
        }

        $tenant = $this->_getTenant();
        $collection = $this->_getCollection();
        $user = $this->_getUser();

        $conceptXml = $Descriptions->item(0);

        $data = array(
            'tenant' => $tenant->code,
            'collection' => $collection->id
        );

        $autoGenerateUri = $this->checkConceptIdentifiers($conceptXml, $doc);

        try {
            $solrDocument = OpenSKOS_Rdf_Parser::DomNode2SolrDocument(
                $conceptXml,
                $data,
                null,
                '',
                $autoGenerateUri,
                $collection
            );
        } catch (OpenSKOS_Rdf_Parser_Exception $e) {
            throw new Zend_Controller_Action_Exception($e->getMessage(), 400);
        }

        //get the Concept based on it's URI:
        $concept = $this->model->getConcept($solrDocument['uri'][0]);

        //modify the UUID of the Solr Document:
        if (null !== $concept) {
            $solrDocument->offsetUnset('uuid');
            $solrDocument->offsetSet('uuid', $concept['uuid']);

            // Preserve any old data which is not part of the rdf.
            if (isset($concept['created_by'])) {
                $solrDocument->offsetSet('created_by', $concept['created_by']);
            }
            if (isset($concept['modified_by'])) {
                $solrDocument->offsetSet('modified_by', $concept['modified_by']);
            }
            if (isset($concept['approved_by'])) {
                $solrDocument->offsetSet('approved_by', $concept['approved_by']);
            }
            if (isset($concept['deleted_by'])) {
                $solrDocument->offsetSet('deleted_by', $concept['deleted_by']);
            }
            if (isset($concept['toBeChecked'])) {
                $solrDocument->offsetSet('toBeChecked', $concept['toBeChecked']);
            }
        }

        $this->validatePrefLabel($solrDocument);

        if ($this->getRequest()->getActionName() == 'put') {
            if (!$concept) {
                throw new Zend_Controller_Action_Exception('Concept `'.$solrDocument['uri'][0].'` does not exists, try POST-ing it to create it as a new concept.', 404);
            }
        } else {
            if ($concept) {
                throw new Zend_Controller_Action_Exception('Concept `'.$solrDocument['uri'][0].'` already exists', 409);
            }
        }

        try {
            $solrDocument->save(true);
        } catch (OpenSKOS_Solr_Exception $e) {
            throw new Zend_Controller_Action_Exception('Failed to save Concept `'.$solrDocument['uri'][0].'`: '.$e->getMessage(), 400);
        }

        $this->getResponse()->setHeader('Content-Type', 'text/xml; charset="utf-8"', true);

        if ($this->getRequest()->getActionName() == 'post') {
            $location = $this->view->serverUrl() . $this->view->url(array(
                'controller' => 'concept',
                'action' => 'get',
                'module' => 'api',
                'id' => $solrDocument['uuid'][0]
            ), 'rest', true);

            $this->getResponse()
                ->setHeader('Location', $location)
                ->setHttpResponseCode(201);
        } else {
            $this->getResponse()->setHttpResponseCode(200);
        }

        $savedConcept = $this->model->getConcept($solrDocument['uuid'][0]);

        // We validate the pref label after commit as well.
        // To prevent duplicates when simultaneously commits happen.
        $this->validatePrefLabel($savedConcept, true, $concept);

        echo $savedConcept->toRDF()->saveXml();
    }

    public function putAction()
    {
        $this->postAction();
    }

    public function deleteAction()
    {
        $this->view->errorOnly = true;
        $this->getHelper('layout')->disableLayout();
        $this->getHelper('viewRenderer')->setNoRender(true);

        $tenant = $this->_getTenant();
        $collection = $this->_getCollection();

        $concept = $this->_fetchConcept();

        $this->getResponse()
            ->setHeader('Content-Type', 'text/xml; charset="utf-8"', true)
            ->setHttpResponseCode(202);
        echo $concept->toRDF()->saveXml();
        $concept->delete(true);
    }

    /**
     * @return OpenSKOS_Db_Table_Row_Tenant
     */
    protected function _getTenant()
    {
        static $tenant;

        if (null === $tenant) {
            //need a tenant and a collection:
            $tenantCode = $this->getRequest()->getParam('tenant');
            if (!$tenantCode) {
                throw new Zend_Controller_Action_Exception('No tenant specified', 412);
            }
            $model = new OpenSKOS_Db_Table_Tenants();
            $tenant = $model->find($tenantCode)->current();
            if (null === $tenant) {
                throw new Zend_Controller_Action_Exception('No such tenant: `'.$tenantCode.'`', 404);
            }
        }

        return $tenant;
    }

    /**
     * @return OpenSKOS_Db_Table_Row_Collection
     */
    protected function _getCollection()
    {
        $collectionCode = $this->getRequest()->getParam('collection');
        if (!$collectionCode) {
            throw new Zend_Controller_Action_Exception('No collection specified', 412);
        }

        $model = new OpenSKOS_Db_Table_Collections();
        $collection = $model->findByCode($collectionCode, $this->_getTenant());
        if (null === $collection) {
            throw new Zend_Controller_Action_Exception('No such collection: `'.$collectionCode.'`', 404);
        }
        return $collection;
    }

    /**
     * @return OpenSKOS_Db_Table_Row_User
     */
    protected function _getUser()
    {
        $apikey = $this->getRequest()->getParam('key');
        var_dump($apikey); exit;
        if (!$apikey) {
            throw new Zend_Controller_Action_Exception('No key specified', 412);
        }
        $user = OpenSKOS_Db_Table_Users::fetchByApiKey($apikey);
        if (null === $user) {
            throw new Zend_Controller_Action_Exception('No such API-key: `'.$apikey.'`', 401);
        }

        if (!$user->isApiAllowed()) {
            throw new Zend_Controller_Action_Exception('Your user account is not allowed to use the API', 401);
        }

        if ($user->active != 'Y') {
            throw new Zend_Controller_Action_Exception('Your user account is blocked', 401);
        }

        return $user;
    }

    /**
     * Validates pref label for saving concept.
     * @param OpenSKOS_Solr_Document|Api_Models_Concept $concept
     * @param bool $isAfterCommit
     * @param null|Api_Models_Concept $previousState
     * @throws Zend_Controller_Action_Exception
     */
    protected function validatePrefLabel($concept, $isAfterCommit = false, $previousState = null)
    {
        if ($concept instanceof Api_Models_Concept) {
            $editorConcept = new Editor_Models_Concept($concept);
        } elseif ($concept instanceof OpenSKOS_Solr_Document) {
            $editorConcept = $this->docToEditorConcept($concept);
        } else {
            throw new \RuntimeException(
                '$concept is not instace of Api_Models_Concept or OpenSKOS_Solr_Document.'
            );
        }

        $prefLabelValidator = Editor_Models_ConceptValidator_UniquePrefLabelInScheme::factory();
        $isUniquePrefLabel = $prefLabelValidator->isValid($editorConcept, []);

        if (!$isUniquePrefLabel) {
            if ($isAfterCommit) {
                $this->rollbackConcept($concept, $previousState);
            }

            throw new Zend_Controller_Action_Exception($prefLabelValidator->getError()->getMessage(), 409);
        }
    }

    /**
     * Transforms sold doc to the editor model concept.
     * May be used to refactor all the code. But may miss some things.
     * @param OpenSKOS_Solr_Document $solrDocument
     * @return \Editor_Models_Concept\
     */
    protected function docToEditorConcept(OpenSKOS_Solr_Document $solrDocument)
    {
        $data = $solrDocument->toArray();
        if (!empty($data['tenant']) && is_array($data['tenant'])) {
            $data['tenant'] = $data['tenant'][0];
        }

        if (!empty($data['uuid']) && is_array($data['uuid'])) {
            $data['uuid'] = $data['uuid'][0];
        }

        return new Editor_Models_Concept(new Api_Models_Concept($data));
    }

    /**
     * Rollback a concept to a previous state. If no previous state - purge the concept.
     * @param Api_Models_Concept $concept
     * @param null|Api_Models_Concept $oldState
     */
    protected function rollbackConcept(Api_Models_Concept $concept, $previousState)
    {
        if ($previousState !== null) {
            // Save the previous state with all its extra data.
            (new Editor_Models_Concept($previousState))->update([], [], true, true);
        } else {
            $concept->purge(true);
        }
    }

    /**
     * Check if we need to generate or not concept identifiers (notation and uri).
     * Validates any existing identifiers.
     * @param DOMNode $Description
     * @param DOMDocument $doc
     * @return boolean If an uri must be autogenerated
     */
    protected function checkConceptIdentifiers(DOMNode $Description, DOMDocument $doc)
    {
        // We return if an uri must be autogenerated
        $autoGenerateUri = false;

        $autoGenerateIdentifiers = filter_var(
            $this->getRequest()->getParam('autoGenerateIdentifiers', false),
            FILTER_VALIDATE_BOOLEAN
        );

        $xpath = new DOMXPath($doc);
        $notationNodes = $xpath->query('skos:notation', $Description);
        $uri = $Description->getAttributeNS(OpenSKOS_Rdf_Parser::$namespaces['rdf'], 'about');

        if ($autoGenerateIdentifiers) {
            if ($uri || $notationNodes->length > 0) {
                throw new Zend_Controller_Action_Exception(
                    'Parameter autoGenerateIdentifiers is set to true, but the xml already contains notation (skos:notation) and/or uri (rdf:about).',
                    400
                );
            }
            $autoGenerateUri = true;
        } else {
            // Is uri missing
            if (!$uri) {
                throw new Zend_Controller_Action_Exception(
                    'Uri (rdf:about) is missing from the xml. You may consider using autoGenerateIdentifiers.',
                    400
                );
            }

            // Is notation missing
            if ($notationNodes->length == 0) {
                throw new Zend_Controller_Action_Exception(
                    'Notation (skos:notation) is missing from the xml. You may consider using autoGenerateIdentifiers.',
                    400
                );
            }

            // Is uri based on notation
            if (!OpenSKOS_Db_Table_Notations::isContainedInUri($uri, $notationNodes->item(0)->nodeValue)) {
                throw new Zend_Controller_Action_Exception(
                    'The concept uri (rdf:about) must be based on notation (must contain the notation)',
                    400
                );
            }

            $autoGenerateUri = false;
        }

        return $autoGenerateUri;
    }
}
