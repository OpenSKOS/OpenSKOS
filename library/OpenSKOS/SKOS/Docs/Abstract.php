<?php
abstract class OpenSKOS_SKOS_Docs_Abstract implements ArrayAccess
{
	/**
	 * @var $_data array
	 */
	protected $_data;
	
	public function __construct(array $solrDoc = array())
	{
		if (null !== $solrDoc) {
			$this->loadFromSolrDoc($solrDoc);
		}
	}
	
	/**
	 * 
	 * @param $solrDoc array
	 * @return OpenSKOS_SKOS_Docs_Abstract
	 */
	public function loadFromSolrDoc(array $solrDoc)
	{
		$this->_data = $solrDoc;
		return $this;
	}
	
	/**
	 * @param offset
	 */
	public function offsetExists ($offset) 
	{
		return isset($this->_data[$offset]);
	}

	/**
	 * @param offset
	 */
	public function offsetGet ($offset) 
	{
		return $this->offsetExists($offset) ? $this->_data[$offset] : null;
	}

	/**
	 * @param offset
	 * @param value
	 * @return OpenSKOS_SKOS_Docs_Abstract
	 */
	public function offsetSet ($offset, $value) 
	{
		if (null === $offset) {
			throw new OpenSKOS_SKOS_Exception('You can only set data ass associative array');
		}
		$this->_data[$offset] = $value;
		return $this;
	}

	/**
	 * @param offset
	 * @return OpenSKOS_SKOS_Docs_Abstract
	 */
	public function offsetUnset ($offset) 
	{
		unset($this->_data[$offset]);
		return $this;
	}
}