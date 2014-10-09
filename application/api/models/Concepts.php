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

class Api_Models_Concepts
{
	protected $_queryParameters = array();
    
    /**
     * @var OpenSKOS_Solr 
     */
    protected $_solr;
	
	public function setQueryParams(Array $parameters)
	{
		$this->_queryParameters += $parameters;
		return $this;
	}
	
	public function getQueryParam($key, $default = null)
	{
		return isset($this->_queryParameters[$key])
			? $this->_queryParameters[$key]
			: $default;
	}
	
	public function setQueryParam($key, $value)
	{
		$this->_queryParameters[$key] = $value;
		return $this;
	}
	
	/**
	 * @return Editor_Models_Concepts
	 */
	public static function factory()
	{
		return new Api_Models_Concepts();
	}
	
	public function getConcepts($q, $includeDeleted = false, $forAutocomplete = false)
	{	
		$solr = $this->solr();
		if(true === (bool)ini_get('magic_quotes_gpc')) {
			$q = stripslashes($q);
		}
		if (null !== ($lang = $this->lang)) {
			$solr->setLang($lang);
		}
		
		//if user request fields, make sure that some fields are always included:
		if (isset($this->_queryParameters['fl'])) {
			$this->_queryParameters['fl'] .= ',xmlns,xml';
		}
		
		$params = array('wt' => $this->format === 'xml' ? 'xml' : 'phps') + $this->_queryParameters;
		
		if (isset($this->_queryParameters['lang'])) {
			$q='LexicalLabelsText@'.$lang.':('.$q.')';
		}
		
		//only return non-deleted items:
		if (false === $includeDeleted) {
		    $q = "($q) AND deleted:false";
		}
		
		if (true === $forAutocomplete) {
			$labelReturnField = $this->_getLabelReturnField();
			$params = $params + array(
				'facet' => 'true',
				'facet.field' => $labelReturnField,
				'fq' => $q,
				'facet.mincount' => 1
			);
			$response = $this->solr()
				->setFields(array('uuid', $labelReturnField))
				->limit(0,0)
				->search($q, $params);
			$this->solr()->setFields(array());
			$labels = array();
			foreach ($response['facet_counts']['facet_fields'][$labelReturnField] as $label => $count) {
				$labels[] = $label;
			}
			return $labels;
		}
		
		return $solr->search($q, $params);
	}
	
	protected function _getLabelReturnField()
	{
		$labelReturnField = 'LexicalLabels';
		if (null !== ($labelField = $this->getQueryParam('returnLabel'))) {
			if (preg_match('/^(pref|alt|hidden)Label$/', $labelField)) {
				$labelReturnField = $labelField;
			}
		}
		$lang = $this->lang;
		$labelReturnField .= null===$lang?'':'@'.$lang;
		return $labelReturnField;
	}
	
	public function autocomplete($label, $includeDeleted = false)
	{
		$lang = $this->lang;
		$label = strtolower($label);
		$labelSearchField = 'LexicalLabelsText';
		$labelReturnField = $this->_getLabelReturnField();
		
		if (null !== ($labelField = $this->getQueryParam('searchLabel'))) {
			if (preg_match('/^(pref|alt|hidden)Label$/', $labelField)) {
				$labelSearchField = $labelField.'Text';
			}
		}
		
		$labelSearchField .= null===$lang?'':'@'.$lang;
		
		$q = "{$labelSearchField}:{$label}*";
		
		//only return non-deleted items:
		if (false === $includeDeleted) {
		    $q = "($q) AND deleted:false";
		}
				
		$params = array(
			'facet' => 'true',
			'facet.field' => $labelReturnField,
			'fq' => $q,
			'facet.mincount' => 1
		);
		
		$response = $this->solr()
			->setFields(array('uuid', $labelReturnField))
			->limit(0,0)
			->search($q, $params);
		$this->solr()->setFields(array());
		$labels = array();
		foreach ($response['facet_counts']['facet_fields'][$labelReturnField] as $label => $count) {
			$labels[] = $label;
		}
		return $labels;
	}
	
	/**
	 * Get Broader terms explicitly by the uri's Array, and implicitly by terms who have narrower=uri
	 * @param string $uri
	 * @param array $uris
	 * @param string $lang
	 * @param bool $includeDeleted, optional, default: false
	 */
	public function getRelations(
        $relation,
        $uri,
        Array $uris = array(),
        $lang = null,
        $inScheme = null,
        $includeDeleted = false,
        $offset = 0,
        $limit = 1000
    )
	{
		switch ($relation) {
			case 'semanticRelation':
			case 'related':
				$q = array($relation . ':"' . $uri.'"');
				break;
			case 'broader':
				$q = array('narrower:"' . $uri.'"');
				break;
			case 'narrower':
				$q = array('broader:"' . $uri.'"');
				break;
			case 'broaderTransitive':
				$q = array('narrowerTransitive:"' . $uri.'"');
				break;
			case 'narrowerTransitive':
				$q = array('broaderTransitive:"' . $uri.'"');
				break;
		}
		if (null !== $inScheme)
			$q[0] .= ' AND inScheme:"'.$inScheme.'"';
		if (count($uris)) {
			foreach ($uris as $uri) {
				$q[] = 'uri:"'.$uri.'"';
			}
		}
	
		$fields = array('uuid', 'uri', 'prefLabel', 'inScheme');
		if (null !== $lang) {
            $fields[] = 'prefLabel@' . $lang;
        }
		
		$q = implode(' OR ', $q);
		//only return non-deleted items:
		if (false === $includeDeleted) {
			$q = "($q) AND deleted:false";
		}
		
		$response = $this->solr()
			->setFields($fields)
			->limit($limit, $offset)
			->search($q);
		$this->solr()->setFields(array());
		return $response;
	}
	
	/**
	 * Get transitive mappings.
	 * @param string $uri
	 * @param array $uris
	 * @param string $lang
	 * @param bool $includeDeleted, optional, default: false
	 */
	public function getMappings(
        $mapping,
        $uri,
        Array $uris = array(),
        $lang = null,
        $inScheme = null,
        $includeDeleted = false,
        $offset = 0,
        $limit = 1000
    )
	{
		switch ($mapping) {
			case 'broadMatch':
				$q = array('narrowMatch:"' . $uri.'"');
				break;
			case 'narrowMatch':
				$q = array('broadMatch:"' . $uri.'"');
				break;
			default:
				$q = array($mapping . ':"' . $uri.'"');
				break;
		}
		if (null !== $inScheme)
			$q[0] .= ' AND inScheme:"'.$inScheme.'"';
        
		$fields = array('uuid', 'uri', 'prefLabel', 'inScheme');
		if (null !== $lang) {
            $fields[] ='prefLabel@' . $lang;
        }
		
		$q = implode(' OR ', $q);
		//only return non-deleted items:
		if (false === $includeDeleted) {
			$q = "($q) AND deleted:false";
		}
		
		$response = $this->solr()
			->setFields($fields)
			->limit($limit, $offset)
			->search($q);
		$this->solr()->setFields(array());
		return $response;
	}
	
	/**
	 * 
	 * @param uuid $id (uri/uuid)
	 * @param $includeDeleted, optional, default: false
	 * @return Api_Models_Concept
	 */
	public function getConcept($id, $includeDeleted = false)
	{
		$data = $this->solr()->get($id, array(
			'wt' => $this->format === 'xml' ? 'xml' : 'phps',
			'fl' => $this->getQueryParam('fl', '*')
			),
    		$includeDeleted
		);
		
		if (null === $data) {
			return null;
		}
		
		return is_object($data) ? $data : new Api_Models_Concept($data, $this);
	}
	
	/**
	 * Uri wrapper around getConcept
	 * @param uuid $id
	 * @param bool $includeDeleted, optional, default: false
	 * @return Api_Models_Concept
	 */
	public function getConceptByUri($uri, $includeDeleted = false)
	{
		return $this->getConcept($uri, $includeDeleted);
	}
	
	/**
	 * Get multiple concepts by list of uuids.
	 *
	 * @param array $uuids
	 * @param bool $includeDeleted, optional, default: false
	 * @return array An array of Api_Models_Concept
	 */
	public function getEnumeratedConcepts(array $uuids, $includeDeleted = false)
	{
		$query = 'uuid:"' . implode('" OR uuid:"', $uuids) . '"';
		$this->setQueryParams(array('rows' => count($uuids)));
		$response = $this->getConcepts($query, $includeDeleted);
		
		if ($response['response']['numFound'] == 0) {
			return array();
		}
		
		// Maintain the order of the given uuids.
		$concepts = array();
		foreach ($uuids as $uuid) {
			foreach ($response['response']['docs'] as $j => $doc) {
				if ($doc['uuid'] == $uuid) {
					$concepts[] = new Api_Models_Concept($doc, $this);
					break;
				}
			}
		}
		
		return $concepts;
	}
	
	/**
	 * Get multiple concepts by list of uris.
	 *
	 * @param array $uris
	 * @param bool $includeDeleted, optional, default: false
	 * @return array An array of type uri => Api_Models_Concept
	 */
	public function getEnumeratedConceptsMapByUris(array $uris, $includeDeleted = false)
	{
		$query = 'uri:"' . implode('" OR uri:"', $uris) . '"';
		$this->setQueryParams(array('rows' => count($uris)));
		$response = $this->getConcepts($query, $includeDeleted);
	
		if ($response['response']['numFound'] == 0) {
			return array();
		}
	
		// Maintain the order of the given uuids.
		$concepts = array();
		foreach ($uris as $uri) {
			foreach ($response['response']['docs'] as $j => $doc) {
				if ($doc['uri'] == $uri) {
					$concepts[$doc['uri']] = new Api_Models_Concept($doc, $this);
					break;
				}
			}
		}
	
		return $concepts;
	}
	
	public function __get($key)
	{
		return isset($this->_queryParameters[$key])
			? $this->_queryParameters[$key]
			: null;
	}
	
	/**
	 * @return OpenSKOS_Solr
	 */
	protected function solr()
	{
        if (null === $this->_solr) {
            $this->_solr = OpenSKOS_Solr::getInstance()->cleanCopy();
        }
        
		return $this->_solr;
	}
}
