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

class OpenSKOS_Solr
{
	protected $config = array(
		'host' => 'localhost',
		'port' => 8983,
		'context' => 'openskos',
		'writeHost' => '',
		'writePort' => 0,
		'writeContext' => ''
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
		
		// If there is no write configuration - use the read configuration.
		if (empty($this->config['writeHost'])) {
			$this->config['writeHost'] = $this->config['host'];
		}
		if (empty($this->config['writePort'])) {
			$this->config['writePort'] = $this->config['port'];
		}
		if (empty($this->config['writeContext'])) {
			$this->config['writeContext'] = $this->config['context'];
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
     * Creates new OpenSKOS_Solr with the configuration of the current one.
	 * @return OpenSKOS_Solr
	 */
	public function cleanCopy()
	{
		return new OpenSKOS_Solr($this->config);
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
			
			if ($response->getStatus() != 400) {
				$doc = new DOMDocument();
				$doc->loadHtml($response->getBody());
				if ($nodes = $doc->getElementsByTagName('h1')) {
					$msg = $nodes->item(0)->nodeValue;
				} else {
					$msg = 'Unkown Error in Solr communication';
				}
			} else {
				$msg = 'Unkown Error in Solr communication. Probably bad query syntax.';
			}
			
			throw new OpenSKOS_Solr_Exception($msg, $response->getStatus());
		}
		
		// In rare cases the response type differs from $params['wt'] so we must check the response content type.
		$responseContentType = $response->getHeader('Content-type');
		if (strpos($responseContentType, 'application/xml') !== false) {
			$result = new DOMDocument();
			$result->loadXML($response->getBody());
			
			// If required format is phps but the returned format is xml, then maybe is the case when no results are found.
			// If that is the case then we can convert it to "No results found array".
			if ($params['wt'] == 'phps') {				
				$resultNode = $result->getElementsByTagName('result');
				if ($resultNode->length > 0) {
					$numFound = $resultNode->item(0)->attributes->getNamedItem('numFound');
					if (null !== $numFound && $numFound->value == 0) {
						$result = array('response' => array('numFound' => 0, 'docs' => array()));
					}
				} 
			}
			
		} else {
			$result = unserialize($response->getBody());
		}
		return $result;
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

	
	public function get($id, Array $extraParams = array(), $includeDeleted = false)
	{
		//concept by uuid or uri?
		if (self::isValidUuid($id)) {
			$key = 'uuid';
		} else {
			$key = 'uri';
		}
		
		$q = $key . ':"' . $id.'"';
		if (false === $includeDeleted) {
		    $q = "($q) AND deleted:false";
		}

		$response = $this->search($q, $extraParams);
		if ($response instanceof DOMDocument) {
			$doc = new DOMDocument();
			$doc->loadXML($response->saveXml($response->getElementsByTagName('doc')->item(0)));
			return $doc;
		} else {
			if ($response['response']['numFound']) return $response['response']['docs'][0];
		}
	}
	
	public function add($documents, $commit = null, $optimize = null)
	{	
		if (!is_object($documents)) {
			throw new OpenSKOS_Solr_Exception('Expected an object');
		} 
		if (is_a($documents, 'OpenSKOS_Solr_Document')) {
			$documents->registerOrGenerateNotation();
			$documents = new OpenSKOS_Solr_Documents($documents);
		} elseif(is_a($documents, 'OpenSKOS_Solr_Documents')) {
			foreach ($documents as $currentDocument) {
				$currentDocument->registerOrGenerateNotation();
			}
			//do nothing, just use magic __toString
		} elseif (is_a($documents, 'Api_Models_Concept')) {
		    $documents = '<add>' . $documents .'</add>';
			//do nothing, just use magic __toString
		} elseif (is_a($documents, 'DOMDocument')) {
		    $documents = $documents->saveXml();
		} else {
			throw new OpenSKOS_Solr_Exception('Expected a `OpenSKOS_Solr_Document|OpenSKOS_Solr_Documents|OpenSKOS_Rdf_Parser_Helper|Api_Models_Concept` object, got a `'.get_class($documents).'`');
		}
		
		$this->postXml((string)$documents);
		if (true === $commit) $this->commit();
		if (true === $optimize) $this->optimize();
		return $this;
	}
	
	/**
	 * 
	 * @param string $xml
	 * @throws OpenSKOS_Solr_Exception
	 * @return OpenSKOS_Solr
	 */
	public function postXml($xml)
	{	
		$response = $this->_getClient(true)
			->setUri($this->getUri('update', true))
			->setRawData($xml)
			->setEncType('text/xml')
			->setHeaders('Content-Type', 'text/xml')
			->request('POST');
				
		if ($response->isError()) {
			throw new OpenSKOS_Solr_Exception($response->getMessage());
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
	
	/**
	 * 
	 * @param string $query
	 * @return OpenSKOS_Solr
	 */
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
		
		$response = $this->_getClient(true)
			->setUri($this->getUri('update', true))
			->setParameterGet('stream.body', $deleteMsg)
			->request('GET');
		
		if ($response->isError()) {
			$doc = new DOMDocument();
			$doc->loadHtml($response->getBody());
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
	
	/**
	 * @param string $path, optional, Default: null.
	 * @param bool $isForWriting, optional, Default: false. If set to true the writing configuration will be used.
	 * @return string
	 */
	public function getUri($path = null, $isForWriting = false)
	{
		if ($isForWriting) {
			$host = $this->config['writeHost'];
			$port = $this->config['writePort'];
			$context = $this->config['writeContext'];
		} else {
			$host = $this->config['host'];
			$port = $this->config['port'];
			$context = $this->config['context'];
		}
		
		$uri = 'http://' 
			. $host .':' 
			. $port
			. '/' . ltrim($context, '/');
		if (null !== $path) {
			$uri = rtrim($uri, '/') . '/' . ltrim($path, '/');
		}
		return $uri;
	}
	
	/**
	 * @param $isForWriting bool, optional, Default: false. If set to true the writing configuration will be used.
	 * @return Zend_Http_Client
	 */
	protected function _getClient($isForWriting = false)
	{
		/* Remove client caching because there is a bug somewhere which couses problems with "select" after "postXml". The "select" is sent with empty params.
		static $client;
		if (null === $client) {
			$client = new OpenSKOS_Solr_Client(null, array('timeout' => 60));
			$client->setUri($this->getUri(null, $isForWriting));
		}
		*/
		
		$client = new OpenSKOS_Solr_Client(null, array('timeout' => 60));
		$client->setUri($this->getUri(null, $isForWriting));
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
		
		if ($asDom) {
			$doc = new DOMDocument();
			$doc->loadXML($response->getBody());
			return $doc;
		} else {
			return $response->getBody();
		}	
	}
}