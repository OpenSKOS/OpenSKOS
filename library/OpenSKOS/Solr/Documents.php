<?php

class OpenSKOS_Solr_Documents implements Countable, Iterator
{
	protected $documents = array();
	protected $position = 0;
	
	public function __construct(OpenSKOS_Solr_Document $document = null)
	{
		if (null !== $document) {
			$this->add($document);
		}
	}

	public function count()
	{
		return count($this->documents);
	}
	
    public function rewind() {
    	$this->position = 0;
    }

    public function current() {
        return $this->documents[$this->position];
    }

    public function key() {
    	return $this->position;
    }

    public function next() {
    	++$this->position;
    }

    public function valid() {
    	return isset($this->documents[$this->position]);
    }
    
    public function add(OpenSKOS_Solr_Document $document)
    {
    	$this->documents[] = $document;
    	return $this;
    }
    
    public function __toString()
    {
    	$doc = DOMDocument::loadXML('<add/>');
    	foreach ($this->documents as $document) {
    		$frag = $doc->createDocumentFragment();
    		$frag->appendXml((string)$document);
    		$doc->documentElement->appendChild($frag);
    	}
    	return $doc->saveXml($doc->documentElement);
    }
}