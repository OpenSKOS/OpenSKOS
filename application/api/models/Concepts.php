<?php
class Api_Models_Concepts
{
	protected $_queryParameters = array();
	
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
	 * @return Dashboard_Models_Concepts
	 */
	public function factory()
	{
		return new Api_Models_Concepts();
	}
	
	public function getConcepts($q)
	{
		$solr = $this->solr();
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
		
		return $solr->search($q, $params);
	}
	
	public function autocomplete($label)
	{
		$lang = $this->lang;
		$label = strtolower($label);
		$labelSearchField = 'LexicalLabelsText';
		$labelReturnField = 'LexicalLabels';
		
		if (null !== ($labelField = $this->getQueryParam('searchLabel'))) {
			if (preg_match('/^(pref|alt|hidden)Label$/', $labelField)) {
				$labelSearchField = $labelField.'Text';
			}
		}
		
		if (null !== ($labelField = $this->getQueryParam('returnLabel'))) {
			if (preg_match('/^(pref|alt|hidden)Label$/', $labelField)) {
				$labelReturnField = $labelField;
			}
		}
		
		$labelSearchField .= null===$lang?'':'@'.$lang;
		$labelReturnField .= null===$lang?'':'@'.$lang;
		
		$q = "{$labelSearchField}:{$label}*";
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
	 */
	public function getRelations($relation, $uri, Array $uris = array(), $lang = null)
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
		
		if (count($uris)) {
			foreach ($uris as $uri) {
				$q[] = 'uri:"'.$uri.'"';
			}
		}
		$fields = array('uuid', 'uri', 'prefLabel');
		if (null !== $lang) $fields[] ='prefLabel@'.$lang;
		
		return $this->solr()
			->setFields($fields)
			->limit(1000)
			->search(implode(' OR ', $q));
	}
	
	/**
	 * 
	 * @param uuid $id
	 * @return Api_Models_Concept
	 */
	public function getConcept($id)
	{
		$data = $this->solr()->get($id, array(
			'wt' => $this->format === 'xml' ? 'xml' : 'phps',
			'fl' => $this->getQueryParam('fl', '*')
			)
		);
		if (null === $data) return;
		return is_object($data) ? $data : new Api_Models_Concept($data, $this);
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
		return Zend_Registry::get('OpenSKOS_Solr');
	}

}
