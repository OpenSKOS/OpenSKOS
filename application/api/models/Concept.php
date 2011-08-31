<?php
class Api_Models_Concept implements Countable, ArrayAccess, Iterator
{
	protected $data = array(), $fieldnames = array();
	protected $position = 0;
	
	public function __construct(Array $data)
	{
		$this->data = $data;
		$this->fieldnames = array_keys($data);
	}
	
	public function __set($fieldname, $value)
	{
		$this->offsetSet($fieldname, $value);
	}
	
	public function offsetSet($fieldname, $value) {
		throw new Zend_Exception('You are not allowed to set values');
	}
	
	public function offsetExists($fieldname) {
		return isset($this->data[$fieldname]);
	}
	
	public function offsetUnset($fieldname) {
		throw new Zend_Exception('You are not allowed to unset values');
	}
	
	public function offsetGet($fieldname) {
		return $this->offsetExists($fieldname) ? $this->data[$fieldname] : null;
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
    	return isset($this->fieldnames[$this->position]) && isset($this->data[$this->fieldnames[$this->position]]);
    }
    
	public function toArray()
	{
		return $this->data;
	}
	
	public function toJson()
	{
		return json_encode($this->toArray());
	}
	
	public function getValues($fieldname, $lang = null)
	{
		if (null !== $lang) {
			$fieldname .= '@' . $lang;
		}
		if (isset($this->data[$fieldname])) {
			return $this->data[$fieldname];
		}
	}
	
	public function getLangValues($fieldname, $lang = null) {
		$data = array();
		foreach ($this as $key => $values) {
			if (0 === strpos($key, $fieldname.'@')) {
				list(, $fieldLang) = explode('@', $key);
				if (null!==$lang && $lang != $fieldLang) continue;
				$data[$fieldLang] = $values;
			}
		}
		return count($data) ? $data : null;
	}
	
	public function getRelations($relation, $lang = null)
	{
		$model = new Api_Models_Concepts();
		$response = $model->getRelations(
			$relation,
			$this->data['uri'], 
			isset($this->data[$relation]) ? $this->data[$relation] : array(), 
			$lang
		);
		$docs = array();
		foreach ($response['response']['docs'] as $doc) {
			$docs[] = new Api_Models_Concept($doc);
		}
		return count($docs) ? $docs : null;
	}
	
	public function __toString()
    {
    	$doc = DOMDocument::loadXML('<doc/>');
    	foreach ($this->fieldnames as $fieldname) {
    		foreach ($this->data[$fieldname] as $value) {
    			$doc->documentElement->appendChild($doc->createElement('field', $value))->setAttribute('name', $fieldname);
    		}
    	}
    	return $doc->saveXml($doc->documentElement);
    }
}