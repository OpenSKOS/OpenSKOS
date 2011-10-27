<?php

class OpenSKOS_Oai_Pmh_Harvester_Records implements Iterator, Countable
{
	/**
	 * @var $_xpath DOMXPath
	 */
	protected $_xpath;
	
	protected $_record = 0;
	
	/**
	 * @var $_records DOMNodeList
	 */
	protected $_records;
	
	public function __construct (Zend_Http_Response $response)
	{
		$doc = new DOMDocument();
		if (!@$doc->loadXml($response->getBody())) {
			throw new OpenSKOS_Oai_Pmh_Harvester_Exception('Failed to load XML from responseBody');
		}
		
		
		$this->_xpath = new DOMXPath($doc);
		$this->_xpath->registerNamespace('rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#');
		$this->_xpath->registerNamespace('skos', 'http://www.w3.org/2004/02/skos/core#');
		$this->_xpath->registerNamespace('oai', 'http://www.openarchives.org/OAI/2.0/');
		
		$errors = $this->_xpath->query('/oai:OAI-PMH/oai:error');
		if ($errors->length) {
			throw new OpenSKOS_Oai_Pmh_Harvester_Exception($errors->item(0)->nodeValue);
		}

		$this->_records = $this->_xpath->query('/oai:OAI-PMH/oai:ListRecords/oai:record');
		
	}
	
	/**
	 * @return DOMNodeList
	 */
	public function getRecords()
	{
		return $this->_records;
	}
	
	public function count()
	{
		return $this->getRecords()->length;
	}
	
	public function getResumptionToken()
	{
		$node = $this->_xpath->query('/oai:OAI-PMH/oai:ListRecords/oai:resumptionToken')->item(0);
		if (null !== $node && $node->nodeValue) {
			return $node->nodeValue;
		}
	}
	
	public function current () 
	{
		return new OpenSKOS_Oai_Pmh_Harvester_Records_Record(
			$this->getRecords()->item($this->key())
		);
	}

	public function next () 
	{
		++$this->_record;
	}

	public function key () 
	{
		return $this->_record;
	}

	public function valid () 
	{
		return null !== $this->getRecords()->item($this->key());
	}

	public function rewind () 
	{
		$this->_record = 0;
	}
}
