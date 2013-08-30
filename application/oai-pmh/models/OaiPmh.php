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

class OaiPmh
{
    const XS_DATETIME_FORMAT = "Y-m-d\TH:i:s\Z";
    
    protected $_verbs = array(
      "Identify" => array(),
      "ListMetadataFormats" => array('identifier'),
      "ListSets" => array('resumptionToken'),
      "GetRecord" => array('identifier', 'metadataPrefix'),
      "ListIdentifiers" => array('from', 'until', 'metadataPrefix', 'set', 'resumptionToken', 'q', 'rows'),
      "ListRecords" => array('from', 'until', 'metadataPrefix', 'set', 'resumptionToken', 'q', 'rows')
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
	
	public function getParam($key, $default = null)
	{
		if (isset($this->_params[$key])) {
			return $this->_params[$key];
		} elseif (null!==$default) {
			return $default;
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
	
	public function getParams($filterOnValidOaiParams = false)
	{
	    if (false === $filterOnValidOaiParams) {
    		return $this->_params;
	    } else {
	        $verb = $this->getParam('verb');
	        if (! $verb|| !isset($this->_verbs[$verb])) return array();
	        $params = array('verb' => $verb);
	        foreach ($this->_verbs as $verb => $extraParameters) {
	            foreach ($extraParameters as $extraParameter) {
	                if (null !== ($param = $this->getParam($extraParameter))) {
	                    //only valid dates!
	                    if ($extraParameter == 'from' || $extraParameter == 'until') {
	                        try {
	                            new Zend_Date($param, Zend_Date::ISO_8601);
	                            $params[$extraParameter] = $this->_params[$extraParameter];
	                        } catch (Zend_Date_Exception $e) {
	                            
	                        }
	                    } elseif ($extraParameter == 'identifier') {
	                        if (OpenSKOS_Solr::isValidUuid($this->_params[$extraParameter])) {
	                            $params[$extraParameter] = $this->_params[$extraParameter];
	                        }
	                    } else {
	                        $params[$extraParameter] = $this->_params[$extraParameter];
	                    }
	                }
	            }
	        }
	        return $params;
	    }
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
			unset($this->_params['verb']);
		    throw new OaiPmh_Exception(
				'Verb `'.$verb.'` is not a valid OAI-PMH verb', 
				OaiPmh_Exception::badVerb
			);
		}
		
		//low level check for double parameters:
		$queryString = '&'.ltrim($_SERVER['QUERY_STRING'], '&');
		parse_str($queryString, $queryStringAsArray);
		foreach ($queryStringAsArray as $qParameter => $value) {
		    $c = 0;
		    str_replace("&{$qParameter}=", '', $queryString, $c);
		    if ($c > 1) {
		        throw new OaiPmh_Exception(
		        'You can use parameter `'.$qParameter.'` only once',
		        OaiPmh_Exception::badArgument
		        );
		    }
		}
		
		foreach ($this->getParams() as $key => $value) {
			if ($key=='verb') continue;
			if (!in_array($key, $this->_verbs[$verb])) {
			    unset($this->_params[$key]);
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
			
			//check for other parameters (which is illegal):
			if(count($queryStringAsArray) > 2) {
				throw new OaiPmh_Exception(
					'Illegal use of the resumptionToken: too many arguments, only `verb` is allowed', 
					OaiPmh_Exception::badArgument
				);
			}
			
			$this->setParams($params);
			return $this->checkparams($verb);
		}
		
		$from = null;
		$until = null;
		
		if (null !== ($request_from = $this->getParam('from'))) {
			try {
				$from = new Zend_Date($request_from, Zend_Date::ISO_8601);
				$this->setParam('from', $from);
			} catch (Zend_Date_Exception $e) {
				throw new OaiPmh_Exception(
					'Date `from` is not a valid ISO8601 format', 
					OaiPmh_Exception::badArgument
				);
			}
		}
		
		if (null !== ($request_until = $this->getParam('until'))) {
			try {
				$until = new Zend_Date($request_until, Zend_Date::ISO_8601);
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
				
			if (strlen($request_from) != strlen($request_until)) {
				throw new OaiPmh_Exception(
					'The `from` and `until` argument must have the same granularity', 
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
		
		if ($verb == 'ListMetadataFormats') {
		    if (null !== ($identifier = $this->getParam('identifier'))) {
    			if (!OpenSKOS_Solr::isValidUuid($identifier)) {
				    throw new OaiPmh_Exception(
					    'argument `identifier` is not a valid identifier (UUID:UUID)', 
					    OaiPmh_Exception::badArgument
				    );				
			    }
    		    $result = OpenSkos_Solr::getInstance()->search('uuid:'.$identifier, array('rows'=>1));
    		    if ($result['response']['numFound']===0) {
    		        throw new OaiPmh_Exception(
    		            'Concept `'.$identifier.'` does not exist in this repository',
    		            OaiPmh_Exception::idDoesNotExist
    		        );
    		    }
		    }
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
	
	public function OAITimestamp($time)
	{
	    $time = new DateTime($time);
	    return $time->format(self::XS_DATETIME_FORMAT);
	    
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
		$model = new OpenSKOS_Db_Table_Tenants();
		$this->_view->tenants = $model->fetchAll();
		
	    $model = new OpenSKOS_Db_Table_Collections();
		$collections = array();
		foreach ($model->fetchAll() as $collection) {
		    if (!isset($collections[$collection->tenant])) {
		        $collections[$collection->tenant] = array();
		    }
		    $collections[$collection->tenant][$collection->id] = $collection;
		}
		$this->_view->collections = $collections;
		
	    $this->_view->assign('conceptSchemes', $this->loadAllConceptSchemes());
	    
	    return $this->_view->render('index/ListSets.phtml');
	}
	
	public function loadAllConceptSchemes($groupByCollection = true)
	{
		$limit = 20;
	    $start = 0;
	    $conceptSchemes = array();
	    while (true) {
	        $params = array('limit' => $limit, 'start' => $start, 'fl' => 'uri,uuid,xml,tenant,collection,dcterms_title');
    	    $response = new OpenSKOS_SKOS_ConceptSchemes(OpenSKOS_Solr::getInstance()->search('class:ConceptScheme', $params));
    	    foreach ($response as $doc) {
    	    	if (!isset($conceptSchemes[$doc['tenant']])) {
    	    		$conceptSchemes[$doc['tenant']] = array();
    	    	}
	    	    if ($groupByCollection) {
		    		if (!isset($conceptSchemes[$doc['tenant']][$doc['collection']])) {
		    	   	    $conceptSchemes[$doc['tenant']][$doc['collection']] = array();
		    	    }
		    	    $conceptSchemes[$doc['tenant']][$doc['collection']][$doc['uri']] = $doc;	    	        
	    	    } else {
	    	  		$conceptSchemes[$doc['tenant']][$doc['uri']] = $doc;
    	    	}
    	    }
    	    if ($limit != count($response)) {
    	    	break;
    	    }
    	    $start += $limit;
	    }
	    return $conceptSchemes;
	}
	
	public function ListIdentifiers()
	{
		return $this->ListRecords(true);
	}
	
	public function ListRecords($onlyIdentifiers = false)
	{
		$solr = OpenSKOS_Solr::getInstance();
		
		$q = $this->getParam('q', '*:*');

		if (null!==$this->_set) {
			if (is_a($this->_set, 'OpenSKOS_Db_Table_Row_Tenant')) {
				$q = "({$q}) AND (tenant:\"{$this->_set->code}\")";
			} elseif (is_a($this->_set, 'OpenSKOS_Db_Table_Row_Collection')) {
				$q = "({$q}) AND (collection:{$this->_set->id}) AND (tenant:{$this->_set->tenant})";
			} else {
				$q = "({$q}) AND (collection:{$this->_set['collection']}) AND (tenant:{$this->_set['tenant']}) AND (inScheme:\"{$this->_set['uri']}\")";
			}
		}
		
		$from = $this->getParam('from');
		$until = $this->getParam('until');
		if (null !== $from && null !== $until) {
			$from = date('Y-m-d\TH:i:s\Z', $from->toString(Zend_Date::TIMESTAMP));
			$until = date('Y-m-d\TH:i:s\Z', $until->toString(Zend_Date::TIMESTAMP));
			$q = "({$q}) AND (timestamp:[{$from} TO {$until}])";
		} elseif (null!==$from) {
			$from = date('Y-m-d\TH:i:s\Z', $from->toString(Zend_Date::TIMESTAMP));
			$q = "({$q}) AND (timestamp:[{$from} TO *])";
		} elseif (null!==$until) {
			$until = date('Y-m-d\TH:i:s\Z', $until->toString(Zend_Date::TIMESTAMP));
			$q = "({$q}) AND (timestamp:[* TO {$until}])";
		}
		
		$params = array(
			'sort' => 'uuid asc',
			'fl' => false === $onlyIdentifiers ? '*' : 'uuid,timestamp,ConceptSchemes,tenant,collection'
		);
		
		$paginator = new Zend_Paginator(new OpenSKOS_Solr_Paginator($q, $params));
		$paginator
			->setItemCountPerPage($this->getParam('rows', self::LIMIT))
			->setCurrentPageNumber($this->getPage());
			
		$this->_view->namespacesByCollection = OpenSKOS_Db_Table_Namespaces::getNamespacesByCollection();
		$this->_view->data = $paginator;
		$this->_view->metadataPrefix = $this->getParam('metadataPrefix');
		$model = new OpenSKOS_Db_Table_Collections();
		$this->_view->collections = $model->fetchAssoc();
		$this->_view->conceptSchemes = $this->loadAllConceptSchemes(false);
		
		return $this->_view->render('index/List'.(false === $onlyIdentifiers ? 'Records' : 'Identifiers').'.phtml');
	}
	
	public function getSet($set)
	{
	    @list($tenantCode, $collectionCode, $conceptSchemaUuid) = explode(':', $set);
	    if (null === $tenantCode) return;
	    $model = new OpenSKOS_Db_Table_Tenants();
	    
	    if (null === ($tenant = $model->find($tenantCode)->current())) {
	        return;
	    }
	    
	    if (null !== $collectionCode) {
			$model = new OpenSKOS_Db_Table_Collections();
			$collection = $model->fetchRow($model->select()
    			->where('code=?', $collectionCode)
    			->where('tenant=?', $tenantCode));
			if (null === $collection) return;
			if (null!==$conceptSchemaUuid) {
			    $params = array('limit' => 1, 'fl' => 'uuid');
			    
			    $response = new OpenSKOS_SKOS_ConceptSchemes(
			        OpenSKOS_Solr::getInstance()->search("class:ConceptScheme AND tenant:{$tenant->code} AND collection:{$collection->id} AND uuid:{$conceptSchemaUuid}")
			    );
			    			    
			    if(count($response)==0) {
			        return;
			    } else {
			        return $response->current();
			    }
			} else {
			    return $collection;
			}
	    } else {
	        return $tenant;
	    }
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
		$this->_view->metadataPrefix = $this->getParam('metadataPrefix');
		$this->_view->namespacesByCollection = OpenSKOS_Db_Table_Namespaces::getNamespacesByCollection();
		
		$this->_view->conceptSchemes = $this->loadAllConceptSchemes(false);
		
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
				try {
					return $this->$verb();
				} catch (Exception $e) {
					$this->_view->errors = array(
						$e->getCodeAsString() => $e->getMessage()
					);
					return $this->_view->render('index/error.phtml');
				}
			} catch (OaiPmh_Exception $e) {
				$this->_view->errors = array(
					$e->getCodeAsString() => $e->getMessage()
				);
				return $this->_view->render('index/error.phtml');
			} catch (Exception $e) {
				$this->_view->errors = array(
					'badArgument' => $e->getMessage()
				);
				return $this->_view->render;
			}
		}
	}
}

class OaiPmh_Exception extends Zend_Exception {
	
	const badArgument = 1;
	const badResumptionToken = 2;
	const badVerb = 3;
	const noSetHierarchy = 4;
	const idDoesNotExist = 5;
	
	public function getCodeAsString()
	{
		$code = $this->getCode();
		switch ($code)
		{
			case self::badArgument: return 'badArgument';
			case self::badResumptionToken: return 'badResumptionToken';
			case self::badVerb: return 'badVerb';
			case self::noSetHierarchy: return 'noSetHierarchy';
			case self::idDoesNotExist: return 'idDoesNotExist';
		    default:
				return 'badArgument';
		}
	}
}
