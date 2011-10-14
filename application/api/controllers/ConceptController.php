<?php
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
		
		$tenant = $this->_getTenant();
		$collection = $this->_getCollection();
		
		// @TODO: move this code to transform Text-XML to object to library
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
		
		$data = array(
			'tenant' => $tenant->code,
			'collection' => $collection->id
		);
		
		try {
			$solrDocument = OpenSKOS_Rdf_Parser::DomNode2SolrDocument($Descriptions->item(0), $data);
		} catch (OpenSKOS_Rdf_Parser_Exception $e) {
			throw new Zend_Controller_Action_Exception($e->getMessage(), 400);
		}
		
		$concept = $this->model->getConcept($solrDocument['uuid'][0]);
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
			$solrDocument->save();
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
		echo $doc->saveXml($Descriptions->item(0));
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
		
		var_dump($concept['tenant'], $concept['collection']);
		$this->getResponse()
			->setHeader('Content-Type', 'text/xml; charset="utf-8"', true)
			->setHttpResponseCode(202);
		echo $concept->toRDF()->saveXml();
		$concept->delete();
	}
	
	/**
	 * @return OpenSKOS_Db_Table_Row_Tenant
	 */
	protected function _getTenant()
	{
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
		$collection = $model->findByCode($collectionCode, $tenant->code);
		if (null === $collection) {
			throw new Zend_Controller_Action_Exception('No such collection: `'.$collectionCode.'`', 404);
		}
		return $collection;
	}
	

}

