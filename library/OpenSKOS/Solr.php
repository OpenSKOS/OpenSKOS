<?php

class OpenSKOS_Solr
{
	protected $config = array(
		'host' => 'localhost',
		'port' => 8983,
		'context' => 'openskos'
	);
	
	protected $limit = 20, $offset = 0, $fields = array('*', 'score'), $langCode;
	
	public function __construct(Array $options = array())
	{
		foreach ($options as $key => $var) {
			if (isset($this->config[$key])) {
				$this->config[$key] = $var;
			} else {
				throw new OpenSKOS_Solr_Exception('Unexpected configuration key `'.$key.'`');
			}
		}
	}
	
	/**
	 * 
	 * @param array $options
	 * @return OpenSKOS_Solr
	 */
	public static function factory(Array $options = array())
	{
		return new OpenSKOS_Solr($options);
	}
	
	/**
	 * 
	 * @param int $limit
	 * @param int $offset
	 * @return OpenSKOS_Solr
	 */
	public function limit($limit, $offset = null)
	{
		$this->limit = (int)$limit;
		if (null !== $offset) {
			$this->offset = (int)$offset;
		}
		return $this;
	}
	
	/**
	 * 
	 * @param array $fields
	 * @return OpenSKOS_Solr
	 */
	public function setFields(Array $fields)
	{
		$this->fields = $fields;
		return $this;
	}
	
	public function setLang($code)
	{
		$this->langCode = $code;
		return $this;
	}
	
	public function search($q, Array $extraParams = array())
	{
		$params = array(
			'q' => $q,
			'rows' => $this->limit,
			'start' => $this->offset,
			'wt' => 'phps',
			'fl' => implode(',', $this->fields),
			'omitHeader' => 'true'
		);
		$params = array_merge($params, $extraParams);
		$params['q'] = $q;
		
		
		$response = $this->_getClient()
			->setUri($this->getUri('select'))
			->setParameterPost($params)
			->request('POST');
		if ($response->isError()) {
			$doc = DOMDocument::loadHtml($response->getBody());
			throw new OpenSKOS_Solr_Exception($doc->getElementsByTagName('pre')->item(0)->nodeValue);
		}
		if ($params['wt'] == 'xml') {
			$response = DOMDocument::loadXML($response->getBody());
		} else {
			$response = unserialize($response->getBody());
		}
		return $response;
	}
	
	/**
	 * Check if a specified uuid is formatted correctly
	 * 
	 * @param string $uuid
	 * @return boolean
	 */
	static public function isValidUuid($uuid)
	{
        if(!is_string($uuid)){
            return false;
        }
        if(empty ($uuid)){
            return false;
        }
		return (boolean)preg_match('/^[a-fA-F0-9]{8}-[a-fA-F0-9]{4}-[a-fA-F0-9]{4}-[a-fA-F0-9]{4}-[a-fA-F0-9]{12}$/', $uuid);
	}
    
	public function get($id, Array $extraParams = array())
	{
		//concept by uuid or uri?
		if (self::isValidUuid($id)) {
			$key = 'uuid';
		} else {
			$key = 'uri';
		}
		$response = $this->search($key . ':"' . $id.'"', $extraParams);
		if (isset($extraParams['wt']) && $extraParams['wt']=='xml') {
			return DOMDocument::loadXML($response->saveXml($response->getElementsByTagName('doc')->item(0)));
		} else {
			if ($response['response']['numFound']) return $response['response']['docs'][0];
		}
	}
	
	public function add($documents)
	{
		if (!is_object($documents)) {
			throw new OpenSKOS_Solr_Exception('Expected an object');
		} 
		if (is_a($documents, 'OpenSKOS_Solr_Document')) {
			$documents = new OpenSKOS_Solr_Documents($documents);
		} elseif(is_a($documents, 'OpenSKOS_Solr_Documents')) {
			//do nothing
		} else {
			throw new OpenSKOS_Solr_Exception('Expected a `OpenSKOS_Solr_Document` or a `OpenSKOS_Solr_Documents` collection, got a `'.get_class($doc).'`');
		}
		
		$response = $this->_getClient()
			->setUri($this->getUri('update'))
			->setRawData((string)$documents)
			->setEncType('text/xml')
			->request('POST');
		if ($response->isError()) {
			$doc = DOMDocument::loadHtml($response->getBody());
			throw new OpenSKOS_Solr_Exception($doc->getElementsByTagName('pre')->item(0)->nodeValue);
		}
		return $this;
	}
	
	public function getUri($path = null)
	{
		$uri = 'http://' 
			. $this->config['host'] .':' 
			. $this->config['port']
			. '/' . ltrim($this->config['context'], '/');
		if (null !== $path) {
			$uri = rtrim($uri, '/') . '/' . ltrim($path, '/');
		}
		return $uri;
	}
	
	/**
	 * @return Zend_Http_Client
	 */
	protected function _getClient()
	{
		static $client;
		if (null === $client) {
			$client = new Zend_Http_Client();
			$client->setUri($this->getUri());
		}
		return $client;
	}
	
}