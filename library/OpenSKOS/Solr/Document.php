<?php

class OpenSKOS_Solr_Document implements Countable, ArrayAccess, Iterator
{
	protected $fieldnames = array();
	protected $data = array();
	protected $position = 0;
	
	public function __set($fieldname, $value)
	{
		$this->offsetSet($fieldname, $value);
	}
	
	public function offsetSet($fieldname, $value) {
		if (!$this->offsetExists($fieldname)) {
			$this->fieldnames[] = $fieldname;
		}
		if (!is_array($value)) {
			$this->data[$fieldname] = array($value);
		} else {
			$this->data[$fieldname] = $value;
		}
	}
	
	public function offsetExists($fieldname) {
		return in_array($fieldname, $this->fieldnames);
	}
	
	public function offsetUnset($fieldname) {
		if (!$this->offsetExists($fieldname)) {
			trigger_error('Undefined index: '.$fieldname, E_USER_NOTICE);
			return;
		}
		unset ( $this->data[$fieldname]);
		$ix = array_search($fieldname, $this->fieldnames);
		unset($this->fieldnames[$ix]);
		$fieldnames = array();
		foreach ($this->fieldnames as $fieldname) $fieldnames[] = $fieldname;
		$this->fieldnames = $fieldname;
		$this->rewind();
	}
	
	public function offsetGet($fieldname) {
		return $this->offsetExists($fieldname) ? $this->data [$fieldname] : null;
	}
	
	public function count()
	{
		return count($this->fieldnames);
	}
	
    public function rewind() {
    	$this->position = 0;
    }

    public function current() {
        return $this->data[$this->fieldnames[$this->position]];
    }

    public function key() {
    	return $this->fieldnames[$this->position];
    }

    public function next() {
    	++$this->position;
    }

    public function valid() {
    	return isset($this->data[$this->fieldnames[$this->position]]);
    }
    
    public function __toString()
    {
    	$doc = DOMDocument::loadXML('<doc/>');
    	foreach ($this->fieldnames as $fieldname) {
    		foreach ($this->data[$fieldname] as $value) {
    			$node = $doc->documentElement->appendChild($doc->createElement('field'));
    			$htmlSafeValue = htmlspecialchars($value);
    			if ($htmlSafeValue == $value) {
	    			$node->appendChild($doc->createTextNode($htmlSafeValue));
    			} else {
    				$node->appendChild($doc->createCDataSection($value));
    			}
    			$node->setAttribute('name', $fieldname);
    		}
    	}
    	return $doc->saveXml($doc->documentElement);
    }
}