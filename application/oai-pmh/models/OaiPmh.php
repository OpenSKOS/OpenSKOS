<?php
class OaiPmh
{
	protected $_verbs = array(
      "Identify" => array(),
      "ListMetadataFormats" => array('identifier'),
      "ListSets" => array('resumptionToken'),
      "GetRecord" => array('identifier', 'metadataPrefix'),
      "ListIdentifiers" => array('from', 'until', 'metadataPrefix', 'set', 'resumptionToken'),
      "ListRecords" => array('from', 'until', 'metadataPrefix', 'set', 'resumptionToken')
	);
	
	protected $_validParams = array(
		"verb",
        "identifier",
        "metadataPrefix",
        "from",
        "until",
        "set",
        "resumptionToken"
	);
	
	public static $dcFields = array(
	    "dc_title",
	    "dc_creator",
	    "dc_subject",
	    "dc_description",
	    "dc_publisher",
	    "dc_contributor",
	    "dc_date",
	    "dc_type",
	    "dc_format",
	    "dc_identifier",
	    "dc_source",
	    "dc_language",
	    "dc_relation",
	    "dc_coverage",
	    "dc_rights"
	);
	
	const LIMIT = 100;
	
	protected $_metadataFormats = array(
		'oai_dc' => array(
			'metadataPrefix' => 'oai_dc',
			'schema' => 'http://www.openarchives.org/OAI/2.0/oai_dc.xsd',
			'metadataNamespace' => 'http://www.openarchives.org/OAI/2.0/oai_dc/'
		),
		'oai_rdf' => array(
			'metadataPrefix' => 'oai_rdf',
			'schema' => 'http://www.openarchives.org/OAI/2.0/rdf.xsd',
			'metadataNamespace' => 'http://www.w3.org/2004/02/skos/core#'
		)
	);
	
	protected $_params = array();
	
	protected $_page = 0;
	
	protected $_template = 'oai-pmh.phtml';
	
	protected $_set;

	/**
	 * 
	 * @var Zend_View_Abstract
	 */
	protected $_view;
	
	/*
	 * Flag for Tidy cleaning the output
	 * 
	 * @var $_cleanOutput;
	 */
	protected $_cleanOutput = false;

	/**
	 * @var $plugin Collectiebeheer_OaiPmh_Model_Plugin_Abstract
	 */
	protected $_plugin;
	
	public function __construct(Array $params, Zend_View $view)
	{
		$this->setParams($params)->setView($view);
		
	}
	
	public function setView (Zend_View_Abstract $view)
	{
		$this->_view = $view;
		return $this;
	}
	
	
	/**
	 * @return Zend_View_Abstract
	 */
	public function getView()
	{
		return $this->_view;
	}
	
	public function setBaseUrl($baseUrl)
	{
		$this->_view->baseUrl = $baseUrl;
		return $this;
	}
	
	public function getParam($key)
	{
		if (isset($this->_params[$key])) {
			return $this->_params[$key];
		}
	}
	
	public function setParam($key, $value)
	{
		$this->_params[$key] = $value;
		return $this;
	}
	
	public function setParams(array $params)
	{
		unset($params['module'], $params['controller'], $params['action'], $params['key']);
		if (isset($params['page'])) {
			$this->setPage($params['page']);
			unset($params['page']);
		} else {
			$this->setPage(1);
		}
		$this->_params = $params;
		return $this;
	}
	
	public function getParams()
	{
		return $this->_params;
	}
	
	public function setPage($page)
	{
		$this->_page = (int)$page;
		return $this;
	}
	
	public function getPage()
	{
		return (int)$this->_page === 0 ? 1 : (int)$this->_page;
	}
	
	public function checkParams($verb)
	{
		if (!isset($this->_verbs[$verb])) {
			throw new OaiPmh_Exception(
				'Verb `'.$verb.'` is not a valid OAI-PMH verb', 
				OaiPmh_Exception::badArgument
			);
		}
		
		foreach ($this->getParams() as $key => $value) {
			if ($key=='verb') continue;
			if (!in_array($key, $this->_verbs[$verb])) {
				throw new OaiPmh_Exception(
					'Verb `'.$verb.'` may not contain parameter `'.$key.'`', 
					OaiPmh_Exception::badArgument
				);
			}
		}

		if (null !== ($resumptionToken = $this->getParam('resumptionToken'))) {
            
			$params = unserialize(base64_decode($resumptionToken));
			if (false === $params || !is_array($params)) {
				throw new OaiPmh_Exception(
					'The given resumptionToken is not a valid one', 
					OaiPmh_Exception::badResumptionToken
				);
			}
			if (!isset($params['verb'])) {
				throw new OaiPmh_Exception(
					'The given resumptionToken is not a valid one (no verb)', 
					OaiPmh_Exception::badResumptionToken
				);
			}
			
			if ($params['verb'] != $verb) {
				throw new OaiPmh_Exception(
					'The given resumptionToken is not a valid one for verb `'.$verb.'`', 
					OaiPmh_Exception::badResumptionToken
				);
			}
			
			$this->setParams($params);
			return $this->checkparams($verb);
		}
		
		if (null !== ($from = $this->getParam('from'))) {
			try {
				$from = new Zend_Date($from, Zend_Date::ISO_8601);
				$this->setParam('from', $from);
			} catch (Zend_Date_Exception $e) {
				throw new OaiPmh_Exception(
					'Date `from` is not a valid ISO8601 format', 
					OaiPmh_Exception::badArgument
				);
			}
		}
		
		if (null !== ($until = $this->getParam('until'))) {
			try {
				$until = new Zend_Date($until, Zend_Date::ISO_8601);
				$this->setParam('until', $until);
			} catch (Zend_Date_Exception $e) {
				throw new OaiPmh_Exception(
					'Date `until` is not a valid ISO8601 format', 
					OaiPmh_Exception::badArgument
				);
			}
		}
		
		if (null!==$until && null!==$from) {
			if ($until->isEarlier($from)) {
				throw new OaiPmh_Exception(
					'The `from` argument must be less than or equal to the `until` argument', 
					OaiPmh_Exception::badArgument
				);
			}
		}
		
		
		if (null !== ($setspec = $this->getParam('set'))) {
			if (!preg_match('/([A-Za-z0-9\-_\.!~\*\'\(\)])+(:[A-Za-z0-9\-_\.!~\*\'\(\)]+)*/', $setspec)) {
				throw new OaiPmh_Exception(
					'setSpec `'.$setspec.'` contains URI reserved characters', 
					OaiPmh_Exception::badArgument
				);
			}
			if (null === ($this->_set = $this->getSet($setspec))) {
				throw new OaiPmh_Exception(
					'setSpec `'.$setspec.'` is not a valid set for this repository', 
					OaiPmh_Exception::badArgument
				);
			}
		}
		
		if (null !== ($metadataPrefix = $this->getParam('metadataPrefix'))) {
			if (!isset($this->_metadataFormats[$metadataPrefix])) {
				throw new OaiPmh_Exception(
					'metadataPrefix `'.$metadataPrefix.'` is not a valid metadataPrefix for this repository', 
					OaiPmh_Exception::badArgument
				);
			}
		} else {
			if ($verb == 'ListIdentifiers' || $verb == 'ListRecords' || $verb == 'GetRecord') {
				throw new OaiPmh_Exception(
					'Missing required argument `metadataPrefix`', 
					OaiPmh_Exception::badArgument
				);
			}
		}
		
		if ($verb=='GetRecord') {
			if (null === ($identifier = $this->getParam('identifier'))) {
				throw new OaiPmh_Exception(
					'Missing required argument `identifier`', 
					OaiPmh_Exception::badArgument
				);
			}
			
			if (!OpenSKOS_Solr::isValidUuid($identifier)) {
				throw new OaiPmh_Exception(
					'argument `identifier` is not a valid identifier (UUID:UUID)', 
					OaiPmh_Exception::badArgument
				);				
			}
			$this->setParam('identifier', $identifier);
		}
		
		$this->_view->parameters = $this->getParams();
	}
	
	public function Identify()
	{
		if (count($this->getParams())!=1) {
			throw new OaiPmh_Exception(
				'Verb Identify may not contain other request parameters', 
				OaiPmh_Exception::badArgument
			);
		}
		$result = OpenSKOS_Solr::getInstance()->search('*:*', array('rows' => 1, 'fl' => 'timestamp', 'sort' => 'timestamp desc'));
		$this->_view->earliestDatestamp = $result['response']['docs'][0]['timestamp'];
		return $this->_view->render('index/Identify.phtml');
	}
	
	public function ListMetadataFormats()
	{
		$params = $this->getParams();
		$keys = array_keys($params);
		if (count($params) > 2) {
			throw new OaiPmh_Exception(
				'Verb ListMetadataFormats may contain only 1 other request parameters (identifier)', 
				OaiPmh_Exception::badArgument
			);
		}
		
		$this->_view->metadataFormats = $this->_metadataFormats;
		
		return $this->_view->render('index/ListMetadataFormats.phtml');
	}
	
	public function ListSets()
	{
		$response = OpenSKOS_Solr::getInstance()
			->search('class:ConceptScheme', array(
				'fl' => '*',
				'rows' => 10000
			));
		if (!count($response['response']['docs'])) {
			throw new OaiPmh_Exception(
				'This repository does not support sets', 
				OaiPmh_Exception::noSetHierarchy
			);
		}
		$this->_view->sets = $response['response'];
		return $this->_view->render('index/ListSets.phtml');
	}
	
	public function ListIdentifiers()
	{
		return $this->ListRecords(true);
	}
	
	public function ListRecords($onlyIdentifiers = false)
	{
		$solr = OpenSKOS_Solr::getInstance();
		
		if (null!==$this->_set) {
			$q = "class:Concept ConceptSchemes:\"{$this->_set['uri']}\"";
		} else {
			$q = '*:*';
		}
		
		$from = $this->getParam('from');
		$until = $this->getParam('until');
		if (null !== $from && null !== $until) {
			$from = date('Y-m-d\TH:i:m\Z', $from->toString(Zend_Date::TIMESTAMP));
			$until = date('Y-m-d\TH:i:m\Z', $until->toString(Zend_Date::TIMESTAMP));
			$q = "({$q}) AND (timestamp:[{$from} TO {$until}])";
		} elseif (null!==$from) {
			$from = date('Y-m-d\TH:i:m\Z', $from->toString(Zend_Date::TIMESTAMP));
			$q = "({$q}) AND (timestamp:[{$from} TO *])";
		} elseif (null!==$until) {
			$until = date('Y-m-d\TH:i:m\Z', $until->toString(Zend_Date::TIMESTAMP));
			$q = "({$q}) AND (timestamp:[* TO {$until}])";
		}
		
		$params = array(
			'sort' => 'prefLabel asc',
			'fl' => false === $onlyIdentifiers ? '*' : 'uuid,timestamp,ConceptSchemes'
		);
		
		$paginator = new Zend_Paginator(new OpenSKOS_Solr_Paginator($q, $params));
		$paginator
			->setItemCountPerPage(self::LIMIT)
			->setCurrentPageNumber($this->getPage());
			
		$this->_view->data = $paginator;
		return $this->_view->render('index/List'.(false === $onlyIdentifiers ? 'Records' : 'Identifiers').'.phtml');
	}
	
	public function getSet($set)
	{
		$solr = OpenSKOS_Solr::getInstance();
		$result = $solr->search('class:ConceptScheme uuid:'.$set, array('rows' => 1, 'fl' => 'uuid,uri'));
		return $result['response']['numFound'] ? $result['response']['docs'][0]: null;
	}
	
	public function GetRecord()
	{
		$identifier = $this->getParam('identifier');
		$result = OpenSkos_Solr::getInstance()->search('uuid:'.$identifier, array('rows'=>1));
		if ($result['response']['numFound']===0) {
			throw new OaiPmh_Exception('Concept `'.$identifier.'` does not exist in this repository');
		}
		$paginator = new Zend_Paginator(new OpenSKOS_Solr_Paginator('uuid:'.$identifier,  array('rows'=>1)));
		$paginator
			->setItemCountPerPage(self::LIMIT)
			->setCurrentPageNumber($this->getPage());
			
		$this->_view->data = $paginator;
		return $this->_view->render('index/ListRecords.phtml');
	}
	
	public function getResumptionToken()
	{
		$params = $this->getParams();
		if (isset($params['from'])) {
			$params['from'] = $params['from']->toString(Zend_Date::ISO_8601);
		}
		if (isset($params['until'])) {
			$params['until'] = $params['until']->toString(Zend_Date::ISO_8601);
		}
		$params['page'] = $this->getPage() + 1;
		return base64_encode(serialize($params));
	}
	
    public function __toString()
	{
		if (null === ($verb = $this->getParam('verb'))) {
			$this->_view->errors = array(
				'badArgument' => 'Required verb is missing'
			);
			return $this->_view->render('index/error.phtml');
		} else {
			try {
				try {
					$this->checkParams($verb);
				} catch(OaiPmh_Exception $e) {
					$this->_view->errors = array(
						$e->getCodeAsString() => $e->getMessage()
					);
					return $this->_view->render('index/error.phtml');
				}
				return $this->$verb();
			} catch (OaiPmh_Exception $e) {
				$this->_view->errors = array(
					$e->getCodeAsString() => $e->getMessage()
				);
				return $this->_view->render('index/error.phtml');
			} catch (Exception $e) {
				$this->_view->errors = array(
					'badArgument' => $e->getMessage()
				);
				$this->_view->content = $this->_view->render('index/error.phtml');
			}
		}
		if ($this->_cleanOutput === true) {
			ob_start();
			echo $this->_view->render($this->_template);
			$xml = ob_get_contents();
			ob_end_clean(); 
			$config = array(
				'indent' => true,
				'input-xml' => true,
				'output-xml' => true,
				'wrap' => 200);

				// Tidy
				$tidy = new tidy;
				$tidy->parseString($xml, $config, "utf8");
				$tidy->cleanRepair();
				return (string)$tidy;
		} else {
			return $this->_view->render($this->_template);
		}
	}
}

class OaiPmh_Exception extends Zend_Exception {
	
	const badArgument = 1;
	const badResumptionToken = 2;
	const badVerb = 3;
	const noSetHierarchy = 4;
	
	public function getCodeAsString()
	{
		$code = $this->getCode();
		switch ($code)
		{
			case self::badArgument: return 'badArgument';
			case self::badResumptionToken: return 'badResumptionToken';
			case self::badVerb: return 'badVerb';
			case self::noSetHierarchy: return 'noSetHierarchy';
			default:
				return 'badArgument';
		}
	}
}
