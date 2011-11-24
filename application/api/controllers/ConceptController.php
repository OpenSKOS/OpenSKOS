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
}

