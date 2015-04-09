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

require_once 'FindConceptsController.php';

class Api_ConceptController extends Api_FindConceptsController {

	public function postAction() 
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
		if($doc->documentElement->nodeName != 'rdf:RDF') {
			throw new Zend_Controller_Action_Exception('Recieved RDF-XML is not valid: expected <rdf:RDF/> rootnode, got <'.$doc->documentElement->nodeName.'/>', 412);
		}
		
		$Descriptions = $doc->documentElement->getElementsByTagNameNs(OpenSKOS_Rdf_Parser::$namespaces['rdf'],'Description');
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
        
		if($this->getRequest()->getActionName() == 'put') {
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
		
		$location = $this->view->serverUrl() . $this->view->url(array(
			'controller' => 'concept',
			'action' => 'get',
			'module' => 'api',
			'id' => $solrDocument['uuid'][0]
		), 'rest', true);
		
        
		$this->getResponse()
			->setHeader('Content-Type', 'text/xml; charset="utf-8"', true)
			->setHeader('Location', $location)
			->setHttpResponseCode(201);
        
		echo $this->model->getConcept($solrDocument['uuid'][0])->toRDF()->saveXml();
	}

	public function putAction() {
		$this->postAction();
	}

	public function deleteAction() {
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
     * @param OpenSKOS_Solr_Document $solrDocument
     * @throws Zend_Controller_Action_Exception
     */
    protected function validatePrefLabel(OpenSKOS_Solr_Document $solrDocument)
    {
        $prefLabelValidator = Editor_Models_ConceptValidator_UniquePrefLabelInScheme::factory();
        $isUniquePrefLabel = $prefLabelValidator->isValid($this->docToEditorConcept($solrDocument), []);
        
        if (!$isUniquePrefLabel) {
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
            if (!($uri && $notationNodes->length > 0)) {
                throw new Zend_Controller_Action_Exception(
                    'Notation (skos:notation) and/or uri (rdf:about) are missing from the xml. You may consider using autoGenerateIdentifiers.',
                    400
                );
            }
            
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

