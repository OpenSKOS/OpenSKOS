<?php
class Api_Models_Concept implements Countable, ArrayAccess, Iterator
{
	protected $data = array(), $fieldnames = array();
	protected $position = 0;
	
	const RDF_NAMESPACE = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';
	const SKOS_NAMESPACE = 'http://www.w3.org/2004/02/skos/core';
	const DC_NAMESPACE = 'http://purl.org/dc/elements/1.1/';

	const SKOS_CLASS_URI = 'http://www.w3.org/2009/08/skos-reference/skos.html';
	
	public static $languageSensitiveClasses = array(
		'LexicalLabels',
		'Notations',
		'DocumentationProperties'
	);
	
	public static $classes = array(
		'ConceptSchemes' => array(
			'conceptScheme',
			'inScheme',
			'hasTopConcept',
			'topConceptOf'
		),
		'LexicalLabels' => array(
			'altLabel',
			'hiddenLabel',
			'prefLabel'
		),
		'Notations' => array(
			'notation'
		),
		'DocumentationProperties' => array(
			'changeNote',
			'definition',
			'editorialNote',
			'example',
			'historyNote',
			'note',
			'scopeNote'
		),
		'SemanticRelations' => array(
			'broader',
			'broaderTransitive',
			'narrower',
			'narrowerTransitive',
			'related',
			'semanticRelation'
		),
		'ConceptCollections' => array(
			'Collection',
			'OrderedCollection',
			'member',
			'memberList'
		),
		'MappingProperties' => array(
			'broadMatch',
			'closeMatch',
			'exactMatch',
			'mappingRelation',
			'narrowMatch',
			'relatedmatch'
		)
	);
	
	/**
	 * @var $model Api_Models_Concepts
	 */
	protected $model;
	
	/**
	 * 
	 * @param array $data
	 * @param Api_Models_Concepts $model
	 */
	public function __construct(Array $data, Api_Models_Concepts $model = null)
	{
		$this->data = $data;
		$this->model = $model;
		$this->fieldnames = array_keys($data);
	}
	
	public function getNamespaces()
	{
		$model = new OpenSKOS_Db_Table_Namespaces();
		$prefixes = $this['xmlns'];
		foreach ($prefixes as &$prefix) {
			$prefix = $model->getAdapter()->quote($prefix);
		} 
		return $model->fetchPairs($model->select()->where('prefix IN ('.implode(',', $prefixes).')'));
	}
	
	/**
	 * 
	 * @param array $data
	 * @param Api_Models_Concepts $model
	 * @return Api_Models_Concept
	 */
	public static function factory($data, Api_Models_Concepts $model = null)
	{
		return new Api_Models_Concept($data, $model);
	}
	
	public function getClass()
	{
		return self::SKOS_NAMESPACE .'#' . $this->data['class'];
	}
	
	public function getClassUri()
	{
		return self::SKOS_CLASS_URI .'#' . $this->data['class'];
	}
	
	public function hasClass($className)
	{
		if (!isset(self::$classes[$className])) {
			throw new Zend_Exception('Class `'.$className.'` does not exist');
		}
		return isset($this->data[$className]) && count($this->data[$className]);
	}
	
	public static function isLanguageSensitiveClass($className)
	{
		return in_array($className, self::$languageSensitiveClasses);
	}
	
	public static function isResolvableUriClass($className)
	{
		return !in_array($className, self::$languageSensitiveClasses);
	}
	
	public static function translate($label)
	{
		return $label;
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
	
	public function getImplicitRelation($relationName)
	{
		$response = $this->model->getRelations($relationName, $this['uri']);
		if (!$response['response']['numFound']) return;
		if (!isset($this->data[$relationName])) return $response['response']['docs'];
		//dedup
		$docs = array();
		foreach ($response['response']['docs'] as $doc) {
			if (!in_array($doc['uri'], $this->data[$relationName])) {
				$docs[] = $doc;
			}
		}
		return count($docs) ? $docs : null;
	}
	
	public function getImplicitRelations()
	{
		$relations = array();
		foreach (self::$classes['SemanticRelations'] as $relationName) {
			$concepts = $this->getImplicitRelation($relationName);
			if (null === $concepts) continue;
			$relations[$relationName] = $concepts;
		}
		return count($relations) ? $relations : null;
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
	
	public function getInstitution()
	{
		$model = new OpenSKOS_Db_Table_Tenants();
		return $model->find($this->data['tenant'])->current();
	}
	
	public function getCollection()
	{
		$model = new OpenSKOS_Db_Table_Collections();
		return $model->find($this->data['collection'])->current();
	}
	
	public function getValues($fieldname, $lang = null)
	{
		if (null !== $lang && self::isLanguageSensitiveClass($fieldname)) {
			$fieldname .= '@' . $lang;
		}
		if (isset($this->data[$fieldname])) {
			return $this->data[$fieldname];
		}
	}
	
	public function getConceptSchemes()
	{
		$ConceptSchemes = array();
		foreach (self::$classes['ConceptSchemes'] as $subClass) {
			if (isset($this->data[$subClass])) {
				$ConceptSchemes[$subClass] = $this->data[$subClass];
			}
		}
		return $ConceptSchemes;
	}
	
	public function getTopConcepts()
	{
		$topConcepts = $this['hasTopConcept'];
		if (!$topConcepts) return;
		$paginator = new Zend_Paginator(new Zend_Paginator_Adapter_Array($topConcepts));
		$paginator
			->setItemCountPerPage(10)
			->setCurrentPageNumber(Zend_Controller_Front::getInstance()->getRequest()->getParam('page'));
		return $paginator;
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
	
    /**
     * @return DOMDocument
     */
	public function toDOM()
	{
    	static $doc;
    	if (null === $doc) {
	    	$doc = DOMDocument::loadXML('<doc/>');
	    	foreach ($this->fieldnames as $fieldname) {
	    		if (is_array($this->data[$fieldname])) {
		    		foreach ($this->data[$fieldname] as $value) {
		    			$doc->documentElement->appendChild($doc->createElement('field', $value))->setAttribute('name', $fieldname);
		    		}
	    		} else {
	    			$doc->documentElement->appendChild($doc->createElement('field', $this->data[$fieldname]))->setAttribute('name', $fieldname);
	    		}
	    	}
    	}
    	return $doc;
	}
	
	/**
	 * @return Api_Models_Concept
	 */
	public function delete()
	{
		//delete this document from Solr:
		$solr = $this->solr()->delete('uuid:'.(is_array($this['uuid']) ? $this['uuid'][0] : $this['uuid']));
		return $this;
	}
	
	
	public function save()
	{
		//add document to Solr:
		$this->solr()->add(new OpenSKOS_Solr_Documents($this));
		return $this;
	}
	
	/**
	 * @return OpenSKOS_Solr
	 */
	protected function solr()
	{
		return Zend_Registry::get('OpenSKOS_Solr');
	}
	
	public function __toString()
    {
    	return $this->toDom()->saveXml($this->toDom()->documentElement);
    }
    
    /**
     * @return DOMDocument
     */
    public function toRDF()
    {
    	$xml = '<rdf:RDF';
    	foreach ($this->getNamespaces() as $prefix => $uri) {
    		$xml .= ' xmlns:'.$prefix.'="'.$uri.'"';
    	}
    	$xml .='>';
    	$xml .= is_array($this['xml']) ? $this['xml'][0] : $this['xml'];
    	$xml .= '</rdf:RDF>';
    	return DOMDocument::loadXml($xml);
    }
}