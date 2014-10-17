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

class Api_Models_Concept implements Countable, ArrayAccess, Iterator
{
	protected $data = array(), $fieldnames = array();
	protected $position = 0;
	protected $rdfModelNs;

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
			'relatedMatch'
		),
		'DctermsDateFields' => array(
			'dcterms_dateSubmitted',
			'dcterms_dateAccepted',
			'dcterms_modified',
			'dcterms_creator'
		)
	);

	protected static $_defaultConceptData = array(
		'xmlns' => array('rdf', 'skos', 'dcterms'),
		'class' => 'Concept'
	);

	/**
	 * Holds an array of the fields which must be set each time a document is edited in solr.
	 * @var array
	 */
	protected $_requiredFields = array ('tenant', 'collection', 'uuid', 'uri', 'notation');

	/**
	 * @var $model Api_Models_Concepts
	 */
	protected $model;

	/**
	 * @var DOMDocument
	 */
	protected $_rdfDocument;

	/**
	 * @var array
	 * Holds the concept languages that have defined fields for the concept;
	 */
	protected $_conceptLanguages;

	/**
	 *
	 * @param array $data
	 * @param Api_Models_Concepts $model
	 */
	public function __construct(Array $data = array(), Api_Models_Concepts $model = null)
	{
		if (empty($data)) {
			$this->data = self::$_defaultConceptData;
			$this->data['uuid'] = OpenSKOS_Utils::uuid();
		} else {
			$this->data = $data;
		}
		$this->model = $model;
		$this->fieldnames = array_keys($data);
		$this->_conceptLanguages = null;
	}

	public function getData()
	{
		return $this->data;
	}

	public function getFields()
	{
		return array_keys($this->data);
	}

	public function getModel()
	{
		return $this->model;
	}

	public function getNamespaces()
	{
		$model = new OpenSKOS_Db_Table_Namespaces();
		//@FIXME Talk to Mark. Clarify what namespaces need to be included.
		$prefixes = array_merge($this['xmlns'], array('dc', 'dcterms', 'skos'));
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
		$relations = $this->getAllRelations($relationName);
		if (empty($relations))
			return ;
		if (!isset($this->data[$relationName])) return $relations;
		//dedup
		$docs = array();
		foreach ($relations as $doc) {
			if (!in_array($doc['uri'], $this->data[$relationName])) {
				$docs[] = $doc;
			}
		}

		return count($docs) ? $docs : null;
	}

	/**
	 * The function tests if a particular URI(established relation) is also internal.
	 * @return boolean isImplicit
	 */
	public function isInternalRelation($uri, $relationName)
	{
		if (isset($this[$relationName]) && is_array($this[$relationName])) {
			return in_array($uri, $this[$relationName]);
		}
		return false;
	}

	/**
	 * Gets all external transitive relations
	 * @param string $relationName
	 * @param string $conceptScheme
	 * @return array of concept records
	 */

	public function getAllRelations($relationName, $conceptScheme = null)
	{
		return $this->getExternalRelations($relationName, $conceptScheme, 'getRelations');
	}

	/**
	 * Gets al external transitive mappings.
	 * @param string $mappingName
	 * @param string $conceptScheme
	 * @return array
	 */

	public function getAllMappings($mappingName, $conceptScheme = null)
	{
		return $this->getExternalRelations($mappingName, $conceptScheme, 'getMappings');
	}

	/**
	 * Wrapper for getting the concepts with external transitive relations.
	 * @param string $fieldName
	 * @param string $conceptScheme
	 * @param string $fname
	 * @return array of documents
	 */
	protected function getExternalRelations($fieldName, $conceptScheme = null, $fname)
	{
		if (null === $this->model) {
			$this->model = Api_Models_Concepts::factory();
        }

        $docs = array();
        $chunkStart = 0;
        $chunkSize = 50;
		do {
            $response = $this->model->$fname(
                $fieldName,
                $this['uri'],
                array(),
                null,
                $conceptScheme,
                false,
                $chunkStart,
                $chunkSize
            );

            if ($response['response']['numFound'] > 0) {
				$docs = array_merge($docs, $response['response']['docs']);
			}

            $chunkStart += $chunkSize;
        } while ($chunkStart < $response['response']['numFound']);

        foreach ($docs as &$doc) {
            $doc['isImplicit'] = true;
        }

		return $docs;
	}

	/**
	 *  Returns the data for concepts associated with an internal field (e.g. broader)
	 * @param string $fieldName
	 * @param string $conceptScheme
	 * @return array of concept documents
	 */
	public function getInternalAssociation($fieldName, $conceptScheme = null)
	{
		if (!isset($this[$fieldName]) || !is_array($this[$fieldName])) {
			return array();
		}

        $relationsUris = array_filter($this[$fieldName]);

		$docs = array();
		$chunkSize = 50;
		for ($chunkStart = 0; $chunkStart < count($relationsUris); $chunkStart += $chunkSize) {

			$chunkOfUris = array_filter(
                array_slice($relationsUris, $chunkStart, $chunkSize)
            );

			$queryParts = array();
			foreach ($chunkOfUris as $conceptUri) {
				$queryParts[] = 'uri:"' . $conceptUri . '"';
			}
			$query = implode(' OR ', $queryParts);

			if (null !== $conceptScheme) {
				$query = 'inScheme:"' . $conceptScheme . '" AND (' . $query . ')';
			}

			$apiModel = Api_Models_Concepts::factory();


            //!NOTE prefLabel@en can cause error "can not use FieldCache on multivalued field: prefLabel" on solr 4
            $fields = array('uuid', 'uri', 'prefLabel', 'inScheme');
            if (null !== $this->getCurrentLanguage()) {
                $fields[] = 'prefLabel@' . $this->getCurrentLanguage();
            }
			$apiModel->setQueryParam('fl', implode(', ', $fields));


            $response = $apiModel->getConcepts($query);

			if ($response['response']['numFound'] > 0) {
				$docs = array_merge($docs, $response['response']['docs']);
			}
		}

		return $docs;
	}
	/**
	 * @TODO This could be used to easily refactor the concept View.
	 *
	 * @param array $fieldNames
	 * @param string $conceptScheme
	 * @param callback $implicitCallback
	 * @return array
	 */
	public function getRelationsArray($fieldNames, $conceptScheme = null, $implicitCallback = null)
	{
		$relations = array();
		foreach ($fieldNames as $fieldName) {
			$relations[$fieldName] = $this->getRelationsByField($fieldName, $conceptScheme, $implicitCallback);
		}
		return $relations;
	}

	/**
	 * Traces all bi-directional relations for a particular fieldname and returns an array holding unique related docs.
	 * e.g. $concept->getRelationsByField('broader', $conceptSchemeUri, array($concept, 'getAllRelations'));
	 * will return transitive broader/narrower relation of the Concept.
	 *
	 * @param string $fieldName
	 * @param string $conceptScheme
	 * @param callback $implicitCallback
	 * @param bool $sortByPrefLabel optional, Default: true.
	 * @return array
	 */
	public function getRelationsByField($fieldName, $conceptScheme = null, $implicitCallback = null, $sortByPrefLabel = true)
	{
		$relations = array();
		$relations = $this->getInternalAssociation($fieldName, $conceptScheme);

		if (null !== $implicitCallback) {
			$implicitRelations = call_user_func_array($implicitCallback, array($fieldName, $conceptScheme));
			if (!empty($implicitRelations)) {
				$relations = array_merge($relations, $implicitRelations);
            }
		}

		$unique = array();
		reset($relations);
		$relations = array_filter($relations, function ($element) use (&$unique) {
			if (in_array($element['uri'], $unique)) {
				return false;
			}
			$unique[] = $element['uri'];
			return true;
		});

		$concepts = array();
		$apiModel = Api_Models_Concepts::factory();
		foreach ($relations as $relation) {
			$concepts[] = new Api_Models_Concept($relation, $apiModel);
		}

		if ($sortByPrefLabel) {
			usort($concepts, array('Api_Models_Concept', 'compareByPreviewLabel'));
		}

		return $concepts;
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
    /**
     * Supports extended behavior. Field filtering and multi language concept label.
     * @param array $fields
     * @return array
     */
	public function toArray(array $fields = array())
	{
		if (!empty($fields)) {
			$result = array();
			foreach ($fields as $field) {
				if (isset($this->data[$field])) {
					$result[$field] = $this->data[$field];
				} else if ($field == 'previewLabel') {
					$result[$field] = $this->getPreviewLabel();
				} else if ($field == 'previewScopeNote') {
					$result[$field] = $this->getMlField('scopeNote', $this->getCurrentLanguage());
				} else if ($field = 'schemes') {
					$result[$field] = $this->getConceptSchemesData();
				}
			}
			return $result;
		} else {
			return $this->data;
		}
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

	public function isDeleted()
	{
	    return (bool)$this['deleted'];
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
    		$doc = new DOMDocument();
	    	$doc->loadXML('<doc/>');
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
	 * Perform a "soft" delete
	 *
	 * @return Api_Models_Concept
	 */
	public function delete($commit = null)
	{
        $rdf = $this->toRDF();
        $data = $this->getCurrentRequiredData();
        $data['deleted'] = true;

        if (isset($this->data['deleted_timestamp'])) {
        	$data['deleted_timestamp'] = $this->data['deleted_timestamp'];
        }

	    $solrDocument = OpenSKOS_Rdf_Parser::DomNode2SolrDocument($rdf->firstChild->firstChild, $data);
	    $this->solr()->add($solrDocument, $commit);

	    if (isset($this['inScheme']) && is_array($this['inScheme'])) {
	    	$this->updateConceptSchemes(array(), $this['inScheme']);
	    }

	    return $this;
	}

	/**
	 * Perform a "hard" delete
	 *
	 * @return Api_Models_Concept
	 */
	public function purge()
	{
	    //delete this document from Solr:
	    $solr = $this->solr()->delete('uuid:'.(is_array($this['uuid']) ? $this['uuid'][0] : $this['uuid']));
	    return $this;
	}

	public function save($extraData = null, $commit = null)
	{
		$document = OpenSKOS_Rdf_Parser::DomNode2SolrDocument($this->toRDF()->documentElement->firstChild, $extraData);

		$this->solr()->add($document, $commit);
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
    	$doc = new DOMDocument();
    	$doc->loadXml($xml);
    	return $doc;
    }

    /**
     *
     */
    protected function getRdfMapping()
    {
    	$languageFields = OpenSKOS_Rdf_Parser::$langMapping;
    	$resourceFields = array_merge(
    			self::$classes['SemanticRelations'],
    			self::$classes['ConceptSchemes'],
    			self::$classes['MappingProperties']
    	);
    	$dctermsDateFields = self::$classes['DctermsDateFields'];
    	$simpleSkosFields = self::$classes['Notations'];
    	return array(
    			'languageFields' => $languageFields,
    			'resourceFields' => $resourceFields,
    			'dctermsDateFields' => $dctermsDateFields,
    			'simpleSkosFields' => $simpleSkosFields,
    	);
    }

    /**
     *
     * @param array $formData
     * @param array $extraData Data which is not included in $formData but is needed for creating the xml or other things.
     * @return Api_Models_Concept
     */
    public function setConceptData(array $formData, array $extraData = array())
    {
    	$this->stripConcept();
    	$this->data = array_merge($this->data, $formData);

    	// Fix for multiplying of notation
    	if (isset($this->data['notation']) && is_array($this->data['notation'])) {
    		$this->data['notation'] = array(array_shift($this->data['notation']));
    	}

    	$this->setDcTermsData($extraData);

    	$document = $this->toRDF();

    	$xpath = new DOMXPath($document);

    	foreach ($this->getNamespaces() as $prefix => $uri) {
    		$xpath->registerNamespace($prefix, $uri);
    	}

    	$this->_rdfDocument = new DOMDocument('1.0', 'utf-8');
    	$this->_rdfDocument->formatOutput = true;
    	$this->_rdfDocument->preserveWhiteSpace = false;

    	$this->_rdfDocument->appendChild($this->_rdfDocument->createElementNS(OpenSKOS_Rdf_Parser::$namespaces['rdf'],'rdf:Description'));
    	$this->_rdfDocument->documentElement->setAttribute('rdf:about', $this->getRdfDescriptionAbout($extraData));

    	$rdfType = $this->_rdfDocument->createElement('rdf:type');
    	$rdfType->setAttribute('rdf:resource', $this->getClass());
    	$this->_rdfDocument->documentElement->appendChild($rdfType);

    	$nodes = $this->getXmlNodes($xpath);

    	foreach ($nodes as $node) {
    		$xmlNode = $this->_rdfDocument->importNode($node, true);
    		$this->_rdfDocument->documentElement->appendChild($xmlNode);
    	}
    	$this->data['xml'] = $this->_rdfDocument->saveXML($this->_rdfDocument->documentElement);

    	return $this;
    }

    /**
     * Sets all dcterms fields.
     *
     * @param unknown_type $extraData
     */
    protected function setDcTermsData($extraData)
    {
    	if (isset($extraData['created_timestamp'])) {
    		$this->data['dcterms_dateSubmitted'] = array($extraData['created_timestamp']);
    	}
    	if (isset($extraData['approved_timestamp'])) {
    		$this->data['dcterms_dateAccepted'] = array($extraData['approved_timestamp']);
    	}
    	if (isset($extraData['modified_timestamp'])) {
    		$this->data['dcterms_modified'] = array($extraData['modified_timestamp']);
    	}
    	if (isset($extraData['created_by'])) {
    		$usersModel = new OpenSKOS_Db_Table_Users();
    		$creator = $usersModel->find($extraData['created_by'])->current();
    		if (null !== $creator) {
    			$this->data['dcterms_creator'] = array($creator->name);
    		}
    	}
    }

    /**
     * Gets xml nodes which will be part of the concept xml
     *
     * @param DOMXPath $xpath
     * @return array
     */
    protected function getXmlNodes(DOMXPath $xpath)
    {
    	$extraNodes = $this->getExtraRdf($xpath);
    	$rdfNodes = $this->getRdfFromData();

    	$nodes = array_merge($rdfNodes, $extraNodes);

    	return $nodes;
    }

    /**
     * Remove old concept data before merging form data.
     * @TODO Refactor.
     */
    protected function stripConcept() {
    	$rdfMapping = $this->getRdfMapping();
    	$languages = $this->getConceptLanguages();
    	foreach ($languages as $languageCode) {
    		foreach ($rdfMapping['languageFields'] as $fieldName) {
    			if (isset($this->data[$fieldName.'@'.$languageCode])) {
    				unset($this->data[$fieldName.'@'.$languageCode]);
    			}
    		}
    	}
    	foreach ($rdfMapping['resourceFields'] as $fieldName) {
    		if (isset($this->data[$fieldName])) {
    			unset($this->data[$fieldName]);
    		}
    	}
    }

    /**
     * Translate data from rdf mapping to rdf nodes
     *
     * @return array
     */
    protected function getRdfFromData()
    {
    	$rdfNodes = array();
    	$rdfMapping = $this->getRdfMapping();
    	foreach ($this->data as $docField => $docValue) {
			$fieldName = explode('@', $docField);
			$fieldName  = $fieldName[0];
    		if ((strpos($docField, '@') !== false) && in_array($fieldName, $rdfMapping['languageFields'])) {
    			$rdfNodes = array_merge($rdfNodes, OpenSKOS_Rdf_Parser::createLanguageField($docField, $docValue));
    		} else if (in_array($docField, $rdfMapping['resourceFields'])) {
    			$rdfNodes = array_merge($rdfNodes, OpenSKOS_Rdf_Parser::createResourceField($docField, $docValue));
    		} else if (in_array($docField, $rdfMapping['dctermsDateFields'])) {

    			if ($docField == 'dcterms_dateAccepted' && (empty($docValue) || empty($docValue[0]))) {
    				continue;
    			}

    			$rdfNodes = array_merge($rdfNodes, OpenSKOS_Rdf_Parser::createDcTermsField($docField, $docValue));

    		} else if (in_array($docField, $rdfMapping['simpleSkosFields'])) {
    			$rdfNodes = array_merge($rdfNodes, OpenSKOS_Rdf_Parser::createSimpleSkosField($docField, $docValue));
    		}
    	}
    	return $rdfNodes;
    }

    /**
     *
     * @param DOMXpath $xpath
     * @return array
     */
    protected function getExtraRdf(DOMXpath $xpath)
    {
    	$extraNodes = array();
    	$innerDocument = $xpath->query('/rdf:RDF/*');
    	foreach ($innerDocument as $rdfContent) {
    		foreach ($rdfContent->childNodes as $childNode) {
    			if (($childNode->nodeType === XML_TEXT_NODE) || (strpos($childNode->nodeName, 'skos') !== false) || (strpos($childNode->nodeName, 'rdf:type') !== false)
    					|| $childNode->nodeName == 'dcterms:dateSubmitted' || $childNode->nodeName == 'dcterms:dateAccepted' || $childNode->nodeName == 'dcterms:modified'
    					|| $childNode->nodeName == 'dcterms:creator' || $childNode->nodeName == 'skos:notation') {
    				continue;
    			}
    			$extraNodes[] = $childNode;
    		}
    	}
    	return $extraNodes;
    }

    /**
     * @return string
     */
    protected function getRdfDescriptionAbout($extraData)
    {
    	if ( ! empty($extraData['uri'])) {
    		return $extraData['uri'];
    	}

    	$doc = $this->toRDF();
    	$descriptions = $doc->documentElement->getElementsByTagNameNs(OpenSKOS_Rdf_Parser::$namespaces['rdf'],'Description');
		if ($descriptions->length == 1) {
			return $descriptions->item(0)->getAttribute('rdf:about');
		} else {
			$url = OpenSKOS_Utils::getAbsoluteUrl(array(
					'module'=> 'api',
					'controller'=> 'find-concept'));
			return $url.'/'.$this->generateUUID();
		}
    }

    /**
     * unique
     */
    protected function generateUUID()
	{
		return OpenSKOS_Utils::uuid();
	}

	/**
	 * @return array
	 */
	public function getConceptLanguages()
	{
		if ( ! empty($this->_conceptLanguages)) {
			return $this->_conceptLanguages;
		}

		return $this->setConceptLanguages();
	}

	/**
	 * @return array
	 */
	protected function setConceptLanguages()
	{
		//@FIXME it's a good idea to have a settings class that deals with .ini parameters, verification & default values.

		$editorSettings = OpenSKOS_Application_BootstrapAccess::getOption('editor');
		$languages = array();
		$this->_conceptLanguages = array();
		if (isset($editorSettings['languages'])) {
			$languages = $editorSettings['languages'];
		}

		foreach ($languages as $languageCode => $languageName) {
			foreach (self::$languageSensitiveClasses as $class) {
				foreach (self::$classes[$class] as $fieldName) {
					if (isset($this[$fieldName.'@'.$languageCode])) {
						$this->_conceptLanguages[] = $languageCode;
						break;
					}
				}
			}
		}

		$this->_conceptLanguages = array_unique($this->_conceptLanguages);

		return $this->_conceptLanguages;
	}

	/**
	 * Returns the the value of a multi language field in a specific language.
	 * @param string $fieldName
	 * @param string $languageCode
	 * @return string
	 */
	public function getMlField($fieldName, $languageCode = null)
	{
		if (null !== $languageCode) {
			$labelKey = $fieldName.'@'.$languageCode;
			if (isset($this[$labelKey]) && is_array($this[$labelKey])) {
				return $this[$labelKey][0];
			}
		}
		if (isset($this[$fieldName]) && is_array($this[$fieldName])) {
			return $this[$fieldName][0];
		}

		return null;
	}

	/**
	 * Extracts language specific fields from a concept.
	 * @param string $class
	 * @param string $languageCode
	 * @return array
	 */
	public function getMlProperties($class, $languageCode)
	{
		$data = array();
		foreach (self::$classes[$class] as $labelField) {
			$labelInLanguage = $labelField.'@'.$languageCode;
			if (isset($this[$labelInLanguage]))
				$data[$labelInLanguage] = $this[$labelInLanguage];
		}
		return $data;
	}

	/**
	 *
	 * @param array $conceptScheme
	 * @return boolean
	 */
	public function isTopConceptOf($conceptSchemeUri)
	{
		$apiClient = new Editor_Models_ApiClient();
		$conceptSchemes = $apiClient->getConceptSchemes($conceptSchemeUri);
		$conceptScheme = array_shift($conceptSchemes);
		$isTopConcept = false;
		if (isset($this['topConceptOf']) && is_array($this['topConceptOf'])) {
			$isTopConcept = in_array($conceptScheme['uri'], $this['topConceptOf']);
		}
		if (isset($conceptScheme['hasTopConcept']) && is_array($conceptScheme['hasTopConcept'])) {
			$isTopConcept = $isTopConcept || in_array($this['uri'], $conceptScheme['hasTopConcept']);
		}
		return $isTopConcept;
	}

	/**
	 * Loading inScheme data.
	 * This is a json utility function
	 * @return array
	 */
	protected function getConceptSchemesData()
	{
		$schemes = array();
		$apiClient = new Editor_Models_ApiClient();

		if (isset($this['inScheme']) && is_array($this['inScheme'])) {
			$schemes = $apiClient->getConceptSchemes($this['inScheme'], $this->data['tenant']);
		}

		return $schemes;
	}

	/**
	 * Remove dependencies when a concept changes schemes
	 * @param arary - uris of all new schemes
	 */
	public function updateConceptSchemes($newSchemes, $oldSchemes)
	{
		foreach ($oldSchemes as $schemeUri) {
			if (!in_array($schemeUri, $newSchemes)) {
				$this->removeFromScheme($schemeUri);
			}
		}
	}

	/**
	 * Removes a concept uri from ConceptScheme resource fields.
	 * @param string $schemeUri
	 */
	public function removeFromScheme($schemeUri)
	{
		$conceptSchemesDocs = Editor_Models_ApiClient::factory()->getConceptSchemes($schemeUri, $this['tenant']);
		if ( ! empty($conceptSchemesDocs)) {
			$conceptScheme = new Editor_Models_ConceptScheme(new Api_Models_Concept(array_shift($conceptSchemesDocs)));
			$conceptScheme->removeTopConcept($this['uri']);
		}
	}

	/**
	 * Get fields that are required by the domnode2solr function.
	 *
	 * @return array
	 */
	public function getCurrentRequiredData()
	{
		$result = array();
		foreach ($this->_requiredFields as $field) {
			if (isset($this->data[$field])) {
				$result[$field] = $this->data[$field];
			}
		}
		return $result;
	}

	/**
	 * Gets the prefLabel of the concept for the current locale.
	 *
	 * @return string
	 */
	public function getPreviewLabel()
	{
		return $this->getMlField('prefLabel', $this->getCurrentLanguage());
	}

    /**
     *
     * @return null|language
     */
    public function getCurrentLanguage()
    {
        if (Zend_Registry::isRegistered('Zend_Locale')) {
			return Zend_Registry::get('Zend_Locale')->getLanguage();
		} else {
			return null;
		}
    }

	/**
	 * Compares two Api_Models_Concept classes by their uuids.
	 *
	 * @param Api_Models_Concept $a
	 * @param Api_Models_Concept $b
	 */
	public static function compare(Api_Models_Concept $a, Api_Models_Concept $b)
	{
		return strcasecmp($a['uuid'], $b['uuid']);
	}

	/**
	 * Compares two Api_Models_Concept classes by their preview labels.
	 *
	 * @param Api_Models_Concept $a
	 * @param Api_Models_Concept $b
	 */
	public static function compareByPreviewLabel(Api_Models_Concept $a, Api_Models_Concept $b)
	{
		return strcasecmp($a->getPreviewLabel(), $b->getPreviewLabel());
	}
}
