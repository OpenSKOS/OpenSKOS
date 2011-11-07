<?php

class OpenSKOS_Oai_Pmh_Harvester_Records extends OpenSKOS_Oai_Pmh_Harvester_Abstract
{
	protected $_namespaces = array(
		'rdf'  => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
		'skos' => 'http://www.w3.org/2004/02/skos/core#',
		'oai'  => 'http://www.openarchives.org/OAI/2.0/'
	);
	
	protected function _getXpathQuery()
	{
		return '/oai:OAI-PMH/oai:ListRecords/oai:record';
	}
	
	public function toArray()
	{
		$result = array();
		foreach ($this as $record) {
			$result[$record->getHeader()->identifier] = $record->metadata();
		}
		return $result;
	}
	
	public function getResumptionToken()
	{
		$node = $this->_xpath->query('/oai:OAI-PMH/oai:ListRecords/oai:resumptionToken')->item(0);
		if (null !== $node && $node->nodeValue) {
			return $node->nodeValue;
		}
	}
}
