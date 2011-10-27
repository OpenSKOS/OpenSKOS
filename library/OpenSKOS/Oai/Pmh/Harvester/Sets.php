<?php

class OpenSKOS_Oai_Pmh_Harvester_Sets implements Iterator, Countable
{
	/**
	 * @var $_xpath DOMXPath
	 */
	protected $_xpath;
	
	protected $_set = 0;
	
	/**
	 * @var $_sets DOMNodeList
	 */
	protected $_sets;
	
	public function __construct (Zend_Http_Response $response)
	{
		$doc = new DOMDocument();
		if (!@$doc->loadXml($response->getBody())) {
			throw new OpenSKOS_Oai_Pmh_Harvester_Exception('Failed to load XML from responseBody');
		}
		
		$this->_xpath = new DOMXPath($doc);
		$this->_xpath->registerNamespace('oai', 'http://www.openarchives.org/OAI/2.0/');
		
		$errors = $this->_xpath->query('/oai:OAI-PMH/oai:error');
		if ($errors->length) {
			throw new OpenSKOS_Oai_Pmh_Harvester_Exception($errors->item(0)->nodeValue);
		}

		$this->_sets = $this->_xpath->query('/oai:OAI-PMH/oai:ListSets/oai:set');
	}
	
	public function toArray()
	{
		$result = array();
		foreach ($this as $set) {
			$result[$set->setSpec] = $set->setName;
		}
		return $result;
	}
	
	/**
	 * @return DOMNodeList
	 */
	public function getSets()
	{
		return $this->_sets;
	}
	
	public function count()
	{
		return $this->getSets()->length;
	}
	
	public function current () 
	{
		return new OpenSKOS_Oai_Pmh_Harvester_Sets_Set (
			$this->getSets()->item($this->key())
		);
	}

	public function next () 
	{
		++$this->_set;
	}

	public function key () 
	{
		return $this->_set;
	}

	public function valid () 
	{
		return null !== $this->getSets()->item($this->key());
	}

	public function rewind () 
	{
		$this->_set = 0;
	}
}
