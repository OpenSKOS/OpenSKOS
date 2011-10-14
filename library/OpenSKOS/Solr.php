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
	 * @return OpenSKOS_Solr
	 */
	public static function getInstance()
	{
		if (Zend_Registry::isRegistered('OpenSKOS_Solr')) {
			return Zend_Registry::get('OpenSKOS_Solr');
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
    
	public static function md5_uuid($value)
	{
		$hash = md5($value);
		return substr($hash, 0, 8) . '-' . substr($hash, 8, 4) 
			. '-' . substr($hash, 12, 4)
			. '-' . substr($hash, 16, 4)
			. '-' . substr($hash, 20);
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
			//do nothing, just use magic __toString
		} elseif (is_a($documents, 'OpenSKOS_Rdf_Parser_Helper')) {
			//do nothing, just use magic __toString
		} else {
			throw new OpenSKOS_Solr_Exception('Expected a `OpenSKOS_Solr_Document|OpenSKOS_Solr_Documents|OpenSKOS_Rdf_Parser_Helper` object, got a `'.get_class($documents).'`');
		}
		
		return $this->postXml((string)$documents);
	}
	
	public function postXml($xml)
	{
		$response = $this->_getClient()
			->setUri($this->getUri('update'))
			->setRawData($xml)
			->setEncType('text/xml')
			->setHeaders('Content-Type', 'text/xml')
			->request('POST');
		if ($response->isError()) {
			$doc = DOMDocument::loadHtml($response->getBody());
			throw new OpenSKOS_Solr_Exception($doc->getElementsByTagName('pre')->item(0)->nodeValue);
		}
		return $this;
	}
	
	protected function _commit_or_update($action , Array $options)
	{
		$updateMsg = '<' . $action;
		foreach ($options as $key => $value) {
			switch($key) {
				case 'waitFlush':
				case 'waitSearcher':
				case 'softCommit':
					$boolValue = (bool)$value === true ? 'true' : 'false';
					$updateMsg .= " {$key}=\"{$boolValue}\"";
					break;
				case 'expungeDeletes':
					if ($action === 'commit') {
						$boolValue = (bool)$value === true ? 'true' : 'false';
						$updateMsg .= " {$key}=\"{$boolValue}\"";
					} else {
						throw new OpenSKOS_Solr_Exception('Unkown attribute `'.$key.'` for <'.$action.'/>');
					}
					break;
				case 'maxSegments':
					if ($action === 'optimize') {
						if (!preg_match('/^\d+$/', $value)) {
							throw new OpenSKOS_Solr_Exception('Expexted a number for attribute `'.$key.'` for <'.$action.'/>');
						}
						$updateMsg .= " {$key}=\"{$value}\"";
					} else {
						throw new OpenSKOS_Solr_Exception('Unkown attribute `'.$key.'` for <'.$action.'/>');
					}
					break;
				default:
					throw new OpenSKOS_Solr_Exception('Unkown attribute `'.$key.'` for <'.$action.'/>');
					
			}
		}
		$updateMsg .= '/>';
		return $this->postXml($updateMsg);
	}
	
	public function delete($query)
	{
		if (preg_match('/^(?:uu)?id\:(.+)/', $query, $match)) {
			if (self::isValidUuid($match[1])) {
				$deleteMsg = '<id>'.$match[1].'</id>';
			} else {
				$deleteMsg = '<query>'.$query.'</query>';
			}
		} else {
			$deleteMsg = '<query>'.$query.'</query>';
		}
		$deleteMsg = '<delete>' . $deleteMsg .'</delete>';
		
		$response = $this->_getClient()
			->setUri($this->getUri('update'))
			->setParameterGet('stream.body', $deleteMsg)
			->request('GET');
		
		if ($response->isError()) {
			$doc = DOMDocument::loadHtml($response->getBody());
			throw new OpenSKOS_Solr_Exception('Delete failed: '.$doc->getElementsByTagName('pre')->item(0)->nodeValue);
		}
		
		return $this;
	}
	
	public function commit(Array $options = array())
	{
		return $this->_commit_or_update('commit', $options);
	}
	
	public function optimize(Array $options = array())
	{
		return $this->_commit_or_update('optimize', $options);
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
	
	/**
	 * Load the uses Solr Schema from the server
	 * @param bool $asDom
	 * @return mixed a DOMDocument if $asDom===true, else a XML string
	 */
	public function getSchema($asDom = true)
	{
		$response = $this->_getClient()
			->setUri($this->getUri('/admin/file/?file=schema.xml'))
			->request('GET');
			
		if ($response->isError()) {
			throw new OpenSKOS_Solr_Exception('Failed to load `schema.xml` from the Solr server');
		}
		return $asDom === true 
			? DOMDocument::loadXML($response->getBody())
			: $response->getBody();
			
	}
}