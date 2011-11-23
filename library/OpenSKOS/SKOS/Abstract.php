<?php

class OpenSKOS_SKOS_Abstract implements Countable, Iterator
{
	protected $_doc = 0;
	
	protected $_docs;
	
	protected $_docClassName;
	
	public function __construct(Array $response)
	{
		if (null === $this->_docClassName) {
			throw new OpenSKOS_SKOS_Exception('No classname set');
		}
		$className = 'OpenSKOS_SKOS_Docs_' . ucfirst($this->_docClassName);
		foreach ($response['response']['docs'] as $doc)
		{
			$this->_docs[] = new $className($doc);
		}
	}
	
	public function count()
	{
		return count($this->_docs);
	}
	
	public function current() 
	{
		return $this->_docs[$this->_doc];
	}

	public function next() 
	{
		$this->_doc++;
	}

	public function key()
	{
		return $this->_doc;
	}

	public function valid() 
	{
		return isset($this->_docs[$this->_doc]);
	}

	public function rewind() 
	{
		$this->_doc = 0;
	}

}