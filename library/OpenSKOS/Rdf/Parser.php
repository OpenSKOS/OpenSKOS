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

require_once dirname(__FILE__) . '/Parser/Exception.php';

class OpenSKOS_Rdf_Parser implements Countable
{
	public static $get_opts = array(
		'verbose|v' => 'Print debug messages to STDOUT',
		'count' => 'Returns the number of documents in the source file',
		'help|?' => 'Print this usage message',
		'from=i' => 'Start at this SKOS "record"',
		'limit=i' => 'Stop at this SKOS "record"',
		'tenant|t=s' => 'The tenant this file belongs to',
		'collection|c=s' => 'The collection this file belongs to',
		'purge|P' => 'Purge. Delete all concept schemes found in the file. (will also delete concepts inside them)',
		'lang|l=s' => 'The default language to use if no "xml:lang" attribute is found',
		'env|e=s' => 'The environment to use (defaults to "production")',
		'commit' => 'Commit to Solr (default: print to STDOUT)',
		'status=s' => 'The status to use for concepts (candidate|approved|expired)',
		'ignoreIncomingStatus' => 'To ignore or not the concept status which comes from the import file',
		'toBeChecked' => 'Sets the toBeCheked status to TRUE',
		'onlyNewConcepts' => 'Import contains only new concepts. Do not update any concepts if they match by notation.',
	);
	
	//@TODO move this to a Concept Class
	static $statuses = array('candidate', 'approved', 'expired');
	
	static $namespaces = array(
		'rdf'      => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
		'rdfs'     => 'http://www.w3.org/2000/01/rdf-schema#',
		'skos'     => 'http://www.w3.org/2004/02/skos/core#',
		'openskos' => 'http://openskos.org/xmlns#',
		'dc'       => 'http://purl.org/dc/elements/1.1/',
		'dcterms'  => 'http://purl.org/dc/terms/',
		'owl'      => 'http://www.w3.org/2002/07/owl#'
	);
	
	public static $langMapping = array(
		'prefLabel',
		'altLabel',
		'hiddenLabel',
		'note',
		'changeNote',
		'definition',
		'editorialNote',
		'example',
		'historyNote',
		'scopeNote',
		'notation'
	);
	
	/**
	 * @var $_collection OpenSKOS_Db_Table_Collection
	 */
	protected $_collection;
	
	protected $_files = array();
	
	protected $_from = 0, $_limit = 1000;
	
	protected $_notImportedNotations = array();
	
	protected $_duplicateConceptSchemes = array();
	
	const MAX_LIMIT = 1000;
	
	const SOLR_DATETIME_FORMAT = "Y-m-d\TH:i:s\Z";
	
	public static $required = array('tenant', 'collection');
	
	/**
	 * 
	 * @param Zend_Console_Getopt $opts
	 */
	protected $_opts;
	
	/**
	 * 
	 * @param Zend_Console_Getopt $opts
	 * @return OpenSKOS_Rdf_Parser
	 */
	public static function factory(Zend_Console_Getopt $opts = null)
	{
		$class = __CLASS__;
		return new $class($opts);
	}
	
	public function __construct(Zend_Console_Getopt $opts = null)
	{
		$this->_opts = $opts;
		if (null !== $opts) {
			$this->setOpts($opts);
		}
		$this->_bootstrap();
	}
	
	/**
	 * @return DOMDocument
	 */
	public function getDOMDocument()
	{
		static $doc, $docFile;
		
		if ($docFile !== $this->getFile()) {
			$docFile = $this->getFile();
			$doc = new DOMDocument('1.0', 'utf-8');
			if (!@$doc->load($docFile)) {
				throw new OpenSKOS_Rdf_Parser_Exception('Failed to load `'.$docFile.'` as DOMDocument');
			}
		}
		return $doc;
	}
	
	/**
	 * @return int
	 */
	public function count()
	{
		$doc = $this->getDOMDocument();
		return $doc->getElementsByTagName('Description')->length;
	}
	
	/**
	 * @return resource a file pointer resource.
	 * @throws OpenSKOS_Rdf_Parser_Exception on fopen error
	 */
	protected function _getFilePointer()
	{
		$file = $this->getFile();
		
		$fp = @fopen($file, 'r');
		if (!$fp) {
			throw new OpenSKOS_Rdf_Parser_Exception('Failed to open `'.$file.'` for reading.');
		}
		return $fp;
	}
	
	/**
	 * @return OpenSKOS_Rdf_Parser
	 */
	public function process_with_xml_parser()
	{
		trigger_error(__METHOD__ .' is deprecated, use '.__CLASS__.'::process()');
	}
	
	public static function getDocNamespaces(DOMDocument $doc)
	{
		$sxe = simplexml_import_dom($doc->documentElement);
        return $sxe->getDocNamespaces();
	}
	
	/**
	 * @return OpenSKOS_Db_Table_Row_Collection
	 */
	public function getCollection()
	{
		return $this->_collection;
	}
	
	/**
	 * Converts a RDF structure to a Solr Document
	 * 
	 * @param DOMNode $Description
	 * @param array $extradata
	 * @param DOMXPath $xpath
	 * @param string $fallbackStatus The status which will be used if no other status is detected.
	 * @return OpenSKOS_Solr_Document
	 */
	public static function DomNode2SolrDocument(
		DOMNode $Description, 
		Array $extradata = array(), 
		DOMXPath $xpath = null,
		$fallbackStatus = '')
	{
		if ($Description->nodeName != 'rdf:Description') {
			throw new OpenSKOS_Rdf_Parser_Exception('wrong nodeName, expected `rdf:Description`, got `'.$Description->nodeName.'`');
		}
		
		if (null === $xpath) {
			$xpath = new DOMXPath($Description->ownerDocument);
			//support for only these namespaces:
			foreach (self::$namespaces as $prefix => $uri) {
				$xpath->registerNamespace($prefix, $uri);
			}
		}
		
		// Sets created_timestamp, modified_timestamp and approved_timestamp.
		$autoExtraData = array();
		$dateSubmittedNodes = $xpath->query('dcterms:dateSubmitted', $Description);
		if ($dateSubmittedNodes->length > 0) {
			$autoExtraData['created_timestamp'] = date(self::SOLR_DATETIME_FORMAT, strtotime($dateSubmittedNodes->item(0)->nodeValue));
		} else {
			$autoExtraData['created_timestamp'] = date(self::SOLR_DATETIME_FORMAT);
		}
		$dateModifiedNodes = $xpath->query('dcterms:modified', $Description);
		if ($dateModifiedNodes->length > 0) {
			$autoExtraData['modified_timestamp'] = date(self::SOLR_DATETIME_FORMAT, strtotime($dateModifiedNodes->item(0)->nodeValue));
		}
		$dateAcceptedNodes = $xpath->query('dcterms:dateAccepted', $Description);
		if ($dateAcceptedNodes->length > 0) {
			$autoExtraData['approved_timestamp'] = date(self::SOLR_DATETIME_FORMAT, strtotime($dateAcceptedNodes->item(0)->nodeValue));
		}
		
		// Sets status. If we have info for date submited the status is candidate, if we have info for date accepted the status is approved.
		if ($dateAcceptedNodes->length > 0) {
			$autoExtraData['status'] = 'approved';
		} else if ($dateSubmittedNodes->length > 0) {
			$autoExtraData['status'] = 'candidate';
		} else if ( ! empty($fallbackStatus)) {
			$autoExtraData['status'] = $fallbackStatus;
		}
		
		// Merges the incoming extra data with the auto detected extra data.
		$extradata = array_merge($autoExtraData, $extradata);
		
		// Set deleted timestamp if status is expired and deleted timestamp is not already set.
		if (! isset($extradata['deleted_timestamp']) 
				&& ((isset($extradata['status']) && $extradata['status'] == 'expired')
					|| (isset($extradata['deleted']) && $extradata['deleted']))) {
			$extradata['deleted_timestamp'] = date(self::SOLR_DATETIME_FORMAT);		
		}
		
		// Fix empty values
		if (empty($extradata['approved_timestamp'])) {
			unset($extradata['approved_timestamp']);
		}
		if (empty($extradata['approved_by'])) {
			unset($extradata['approved_by']);
		}
		if (empty($extradata['deleted_timestamp'])) {
			unset($extradata['deleted_timestamp']);
		}
		if (empty($extradata['deleted_by'])) {
			unset($extradata['deleted_by']);
		}
		
		// Creates the solr document from the description and the extra data.
		$document = new OpenSKOS_Solr_Document();
		foreach ($extradata as $key => $var) {
			$document->$key = is_bool($var) ? (true === $var ? 'true' : 'false'): $var;
		}
		
		if (!isset($extradata['uri'])) {
			$uri = $Description->getAttributeNS(self::$namespaces['rdf'], 'about');
			if (!$uri) {
				throw new OpenSKOS_Rdf_Parser_Exception('missing required attribute rdf:about');
			}
			$document->uri = $uri;
		} else {
			$uri = $extradata['uri'];
		}
		

		if (!isset($extradata['uuid'])) {
			$document->uuid = OpenSKOS_Utils::uuid();
		}
		
		if ($type = ($xpath->query('./rdf:type', $Description)->item(0))) {
			$resource = $type->getAttributeNS(self::$namespaces['rdf'], 'resource');
			if (0 !== strpos($resource, self::$namespaces['skos'])) {
				return;
			}
			$className = parse_url($resource, PHP_URL_FRAGMENT);
			$document->class = parse_url($type->getAttributeNS(self::$namespaces['rdf'], 'resource'), PHP_URL_FRAGMENT);
		} else {
			throw new OpenSKOS_Rdf_Parser_Exception('missing required attribute rdf:type');
		    return;
		}

		
		$skosElements = $xpath->query('./skos:*', $Description);
		foreach ($skosElements as $skosElement) {
			$fieldname = str_replace('skos:', '', $skosElement->nodeName);
			if (in_array($fieldname, self::$langMapping)) {
				if ($xml_lang = $skosElement->getAttribute('xml:lang')) {
					$fieldname = $fieldname . '@'.$xml_lang;
				}
			}
			
			$document->$fieldname = trim($skosElement->nodeValue)
				? trim($skosElement->nodeValue)
				: $skosElement->getAttributeNS(self::$namespaces['rdf'], 'resource');

			//store every first preflabel/notation in a sortable field:
			if (0 === strpos($fieldname, 'prefLabel') || 0 === strpos($fieldname, 'notation')) {
				$sortFieldName = str_replace(array('prefLabel', 'notation'), array('prefLabelSort', 'notationSort'), $fieldname);
				if (!$document->offsetExists($sortFieldName)) {
					$offset = $document->offsetGet($fieldname);
					$document->$sortFieldName = array_shift($offset);
				}
				
				//also store the first language in a generic field:
				if (strpos($fieldname, '@')) {
					$sortFieldName = preg_replace('/@.+/', 'Sort', $fieldname);
					if (!$document->offsetExists($sortFieldName)) {
						$offset = $document->offsetGet($fieldname);
						$document->$sortFieldName = array_shift($offset);
					}
				}
			}
		}
		
		foreach (array('dc', 'dcterms') as $ns) {
			foreach ($xpath->query('./'.$ns.':*', $Description) as $element) {
				$fieldname = str_replace($ns.':', 'dcterms_', $element->nodeName);
				$document->$fieldname = trim($element->nodeValue);
			}
		}
		
		//some XML files use rdfs:label/rdfs:comment
		// let's map those to dcterms:title/dcterms:description
		foreach ($xpath->query('./rdfs:label | ./dcterms:description', $Description) as $element) {
			$fieldname = str_replace(
				array('rdfs:label', 'rdfs:comment'), 
				array('dcterms:title', 'dcterms:description'),
				$element->nodeName
			);
			$document->$fieldname = trim($element->nodeValue);
		}
		
		//infer dcterms:title from skos:prefLabel if not already present, using the first
		// prefLabel found matching one of the following criteria, checked in this order:
		// 1. with xml:lang=XY where XY is lang option (if set)
		// 2. without an xml:lang attribute
		// 3. any prefLabel
		if (!isset($document->dcterms_title)) {
			$prefLabelXpathQueries = array(
				'./skos:prefLabel[not(@xml:lang)]',
				'./skos:prefLabel',
			);
			if (!empty($extradata['lang'])) {
				array_unshift($prefLabelXpathQueries, "./skos:prefLabel[@xml:lang='".$extradata['lang']."']");
			}
			foreach ($prefLabelXpathQueries as $xpathQuery) {
				if ($prefLabelElement = ($xpath->query($xpathQuery, $Description)->item(0))) {
					$prefLabel = trim($prefLabelElement->nodeValue);
					$document->dcterms_title = $prefLabel;
					break;
				}
			}
		}
		
		$document->xml = $Description->ownerDocument->saveXML($Description);
		
		//store namespaces:
		$availableNamespaces = array();
		foreach ($Description->childNodes as $childNode) {
			if ($childNode->nodeType === XML_ELEMENT_NODE) {
				$prefix = preg_replace('/^([a-z0-9\-\_]+)\:.+$/', '$1', $childNode->nodeName);
				if (!in_array($prefix, $availableNamespaces)) {
					$availableNamespaces[] = $prefix;
				} 
			}
		}
		
		if ($availableNamespaces) {
			$document->xmlns = $availableNamespaces;
		}
		
		return $document;
	}
	
    /**
     * Processes an import file.
     * @param int $byUserId, optional If specified some actions inside the processing will be linked to that user
     */
	public function process($byUserId = null)
	{
        // Set our Reconnecting adapter. It will try to reconnect if the db goes away.
        Zend_Db_Table::setDefaultAdapter(
            OpenSKOS_Db_Adapter_Pdo_Mysql_Reconnecting::createFromPdoMysql(
                Zend_Db_Table::getDefaultAdapter()
            )
        );
        
		$xpath = new DOMXPath($this->getDOMDocument());
		//support for only these namespaces:
		foreach (self::$namespaces as $prefix => $uri) {
			$xpath->registerNamespace($prefix, $uri);
		}

        // We need all elements inside <rdf:RDF> tags. No matter if they are on top or not. So we use // instead of /
        $rdfRootXPath = '//rdf:RDF';
		
		//store all Namespaces used by this scheme in Database:
		$namespaces = self::getDocNamespaces($this->getDOMDocument());
		$this->getCollection()->setNamespaces($namespaces);

		$addDoc = new DOMDocument('1.0', 'utf-8');
		$addDoc->appendChild($addDoc->createElement('add'));
		$documents = new OpenSKOS_Solr_Documents();
		
		//sometimes the first nodes of the XML file is a ConceptScheme:
		$ConceptScheme = $xpath->query($rdfRootXPath . '/skos:ConceptScheme')->item(0);
		if ($ConceptScheme) {
			
		    $doc = $this->getDOMDocument();
		    //convert this node to a DOMstructure the parse understands:
		    $node = $doc->createElementNS(
		        'http://www.w3.org/1999/02/22-rdf-syntax-ns#', 
		        'rdf:Description'
		    );
		    $node->setAttributeNS(
		        'http://www.w3.org/1999/02/22-rdf-syntax-ns#', 
		        'rdf:about', 
		        $ConceptScheme->getAttribute('rdf:about')
		    );
		    $node->appendChild(
		        $doc->createElementNS('http://www.w3.org/1999/02/22-rdf-syntax-ns#', 'rdf:type')
		    )->setAttributeNs(
    		    'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
		        'rdf:resource', 
		        "http://www.w3.org/2004/02/skos/core#ConceptScheme"
		    );
		    
		    //clone all dc/dcterms nodes:
		    $dcNodes = $xpath->query($rdfRootXPath . '/dc:* | ' . $rdfRootXPath . '/dcterms:* ');
		    foreach ($dcNodes as $dcNode) {
		        $node->appendChild($dcNode->cloneNode(true));
		    }
		    $data = array(
				'tenant' => $this->getOpt('tenant'),
				'collection' => $this->_collection->id,
				'lang' => $this->getOpt('lang'),
			);
			if ($this->getOpt('status')) $data['status'] = (string)$this->getOpt('status');
			if ($this->getOpt('toBeChecked')) $data['toBeChecked'] = 'true';
			
		    $document = self::DomNode2SolrDocument($node, $data);
		    
			if ($document) {
				if ($this->handleUniqueConceptScheme($document, $byUserId)) {
			    	$documents->add($document);
				}
			}
		}
		
		$notationsCheck = $this->fetchNotationsCheck();
		
		$Descriptions = $xpath->query($rdfRootXPath . '/rdf:Description');
		$d = 0;
		foreach ($Descriptions as $i => $Description) {
		    if ($i < $this->getFrom()) continue;
//			if ($i >= ($this->getFrom() + $this->getLimit())) break;
			
		    // Ignore elements of type collection. May cause the script to hang out if it has too many members.
		    if ($type = ($xpath->query('./rdf:type', $Description)->item(0))) {
				$resource = $type->getAttributeNS(self::$namespaces['rdf'], 'resource');
				$className = parse_url($resource, PHP_URL_FRAGMENT);
				if ($className == 'Collection') {
					continue;
				}
		    }
		    
			if ($d >= self::MAX_LIMIT) {
				$this->_solr()->add($documents);
				$documents = new OpenSKOS_Solr_Documents();
				$d = 0;
			}
			$d++;
            
            // Some basic data
			$data = array(
				'tenant' => $this->getOpt('tenant'),
				'collection' => $this->_collection->id,
				'lang' => $this->getOpt('lang'),
			);
            
			// Check if document with same notation already exists.
			$notationNodes = $xpath->query('skos:notation', $Description);
			if (!$this->handleUniqueNotation($data, $notationNodes, $notationsCheck)) {
                continue;
            }
			
            // Prepare the document to insert
			if ($this->getOpt('toBeChecked')) {
				$data['toBeChecked'] = 'true';
			}
			
			if ($this->getOpt('ignoreIncomingStatus') && $this->getOpt('status')) {
				$data['status'] = (string)$this->getOpt('status');
			}
			
			$document = self::DomNode2SolrDocument($Description, $data, $xpath, (string)$this->getOpt('status'));
			
			if ($document) {                
				$class = $document->offsetGet('class');
				if ($class[0] == 'ConceptScheme') {
					if (!$this->handleUniqueConceptScheme($document, $byUserId)) {
						continue;
					}
				}
				
				$documents->add($document);
			}
		}
		
		if (null!==$this->getOpt('commit')) {
			$this->_solr()->add($documents);
			$this->_solr()->commit();
		} else {
			echo $documents."\n";
		}
	}
    
	public function getDuplicateConceptSchemes() 
	{
		return $this->_duplicateConceptSchemes;
	}
	
	public function getNotImportedNotations()
	{
		return $this->_notImportedNotations;
	}

	public static function uri2uuid($uri)
	{
		$hash = md5($uri);
		return substr($hash, 0, 8) . '-' . substr($hash, 8, 4) 
			. '-' . substr($hash, 12, 4)
			. '-' . substr($hash, 16, 4)
			. '-' . substr($hash, 20);
	}
	
	/**
	 * @return OpenSKOS_Solr
	 */
	protected function _solr()
	{
		return Zend_Registry::get('OpenSKOS_Solr');
	}
	
	/**
	 * @param int $from
	 * @return OpenSKOS_Rdf_Parser
	 */
	public function setFrom($from)
	{
		$this->_from = (int)$from;
		return $this;
	}
	
	/**
	 * @return int $from
	 */
	public function getFrom()
	{
		return $this->_from;
	}
	
	/**
	 * @param int $from
	 * @return OpenSKOS_Rdf_Parser
	 */
	public function setLimit($limit)
	{
		if ((int)$limit > self::MAX_LIMIT) {
			$limit = self::MAX_LIMIT;
		}
		$this->_limit = (int)$limit;
		return $this;
	}
	
	/**
	 * @return int $from
	 */
	public function getLimit()
	{
		return $this->_limit;
	}
	
	public function getOpt($key)
	{
		return $this->_opts->$key;
	}
	
	/**
	 * 
	 * @param Zend_Console_Getopt $opts
	 * @return OpenSKOS_Rdf_Parser
	 */
	public function setOpts(Zend_Console_Getopt $opts)
	{
		try {
		   $opts->parse();
		} catch (Zend_Console_Getopt_Exception $e) {
		    echo str_replace('[ options ]', '[ options ] file', $e->getUsageMessage());
			throw new OpenSKOS_Rdf_Parser_Exception($e->getMessage());
		}
		
		if (null!== $opts->help) {
		    echo str_replace('[ options ]', '[ options ] file', $opts->getUsageMessage());
		    throw new OpenSKOS_Rdf_Parser_Exception('', 0);
		}
		
		if ($opts->status) {
			if (!in_array($opts->status, self::$statuses)) {
				throw new OpenSKOS_Rdf_Parser_Exception('Illegal `status` value, must be one of `'.implode('|', self::$statuses).'`', 0);
			}
		}
		
		foreach (self::$required as $opt) {
			if (null===$this->_opts->$opt) {
				throw new OpenSKOS_Rdf_Parser_Exception("missing required parameter `{$opt}`");
			}
		}
		$this->_opts = $opts;
		
		if (null !== $this->_opts->help) {
			$this->printUsageMessageAndExit();
		}
		
		if (null!==$opts->limit) {
			$this->setLimit((int)$opts->limit);
		}
		
		if (null!==$opts->from) {
			$this->setFrom((int)$opts->from);
		}
		
		$this->_bootstrap();
		
		$files = $this->_opts->getRemainingArgs();
		if (count($files)!==1) {
			throw new OpenSKOS_Rdf_Parser_Exception(str_replace('[ options ]', '[ options ] file', $this->_opts->getUsageMessage()));
		}
		$this->setFiles($files);
		
		$model = new OpenSKOS_Db_Table_Tenants();
		$tenant = $model->find($opts->tenant)->current();
		if (null === $tenant) {
			throw new OpenSKOS_Rdf_Parser_Exception("No such tenant: `{$opts->tenant}`");
		}
		
		$model = new OpenSKOS_Db_Table_Collections();
		if (preg_match('/^\d+$/', $opts->collection)) {
		    $collection = $model->find($opts->collection)->current();
		} else {
		    $collection = $model->findByCode($opts->collection, $opts->tenant);
		}
		if (null === $collection) {
			throw new OpenSKOS_Rdf_Parser_Exception("No such collection: `{$opts->collection}`");
		} else {
			$this->_collection = $collection;
		}
		
		return $this;
	}
	
	/**
	 * @return array
	 */
	public function getFiles()
	{
		return $this->_files;
	}
	
	/**
	 * @return string
	 */
	public function getFile()
	{
		$file = current($this->_files);
//		next($this->_files);
		return $file;
	}
	
	/**
	 * @return Zend_Console_Getopt
	 */
	public function getOpts()
	{
		return $this->_opts;
	}
	
	/**
	 * @return OpenSKOS_Rdf_Parser
	 */
	protected function _bootstrap()
	{
		static $firstRun;
		
		if (null === $firstRun) {
			if ($this->_opts->env && !defined('APPLICATION_ENV')) {
                define('APPLICATION_ENV', $this->_opts->env);
            }
			//bootstrap the application:
			include dirname(__FILE__) . '/../../../public/index.php';
			error_reporting(E_ALL);
			ini_set('display_errors', true);
			$firstRun = false;
		}
		return $this;
	}
	
	/**
	 * @return Bootstrap
	 */
	protected function _getBootstrap()
	{
		return Zend_Controller_Front::getInstance()->getParam('bootstrap');		
	}
	
	/**
	 * @return OpenSKOS_Rdf_Parser
	 */
	public function setFiles(array $files)
	{
		foreach ($files as $file) {
			if (!file_exists($file)) {
				throw new OpenSKOS_Rdf_Parser_Exception("file `{$file} does not exists\n");
			}
			
			if (!is_file($file)) {
				throw new OpenSKOS_Rdf_Parser_Exception("`{$file} is not a file\n");
			}
			
			if (!is_readable($file)) {
				throw new OpenSKOS_Rdf_Parser_Exception("file `{$file} is not readable\n");
			}
			$this->_files[] = $file;
		}
		return $this;
	}
	
	public function printUsageMessageAndExit()
	{
		if (null!==$opts->help) {
			echo str_replace('[ options ]', '[ options ] file', $this->_opts->getUsageMessage());
			exit(0);
		}	
	}
		
	public static function createLanguageField($fieldName, $fieldValues)
	{
		$nodes = array();
		list($fieldName, $fieldLanguage) = explode('@', $fieldName);
		$doc = new DOMDocument('1.0', 'utf-8');

		if (!is_array($fieldValues)) {
			$fieldValues = array($fieldValues);
		}
		
		foreach ($fieldValues as $fieldValue) {
			$node = $doc->createElement('skos:'.$fieldName);
			$node->appendChild($doc->createTextNode($fieldValue));
			
			if (!empty($fieldLanguage)) {
				$node->setAttribute('xml:lang', $fieldLanguage);
			}
			
			$nodes[] = $node;
		}
		return $nodes;
	}
	
	public static function createResourceField($fieldName, $fieldValues)
	{
		$nodes = array();
		$doc = new DOMDocument('1.0', 'utf-8');

		if (!is_array($fieldValues)) {
			$fieldValues = array($fieldValues);
		}
		
		foreach ($fieldValues as $fieldValue) {
			$node = $doc->createElement('skos:'.$fieldName);
			$node->setAttribute('rdf:resource', $fieldValue);
			$nodes[] = $node;
		}
		return $nodes;
	}
	
	/**
	 * Creates simple skos xml element for the field
	 * If $fieldValues is array - create an element for each of them
	 *
	 * @param string $fieldName
	 * @param array|string $fieldValues
	 */
	public static function createSimpleSkosField($fieldName, $fieldValues)
	{
		$nodes = array();
		$doc = new DOMDocument('1.0', 'utf-8');
	
		if (!is_array($fieldValues)) {
			$fieldValues = array($fieldValues);
		}
	
		foreach ($fieldValues as $fieldValue) {
			$node = $doc->createElement('skos:' . $fieldName);
			$node->appendChild($doc->createTextNode($fieldValue));
			$nodes[] = $node;
		}
		return $nodes;
	}
	
	/**
	 * Creates dcterm xml element for the field
	 * If $fieldValues is array - create an element for each of them
	 *
	 * @param string $fieldName
	 * @param array|string $fieldValues
	 */
	public static function createDcTermsField($fieldName, $fieldValues)
	{
		$nodes = array();
		$doc = new DOMDocument('1.0', 'utf-8');
	
		if (!is_array($fieldValues)) {
			$fieldValues = array($fieldValues);
		}
	
		foreach ($fieldValues as $fieldValue) {
			$node = $doc->createElement('dcterms:' . str_ireplace('dcterms_', '', $fieldName));			
			$node->appendChild($doc->createTextNode($fieldValue));
			$nodes[] = $node;
		}
		return $nodes;
	}
	
	/**
	 * Creates dc xml element for the field
	 * If $fieldValues is array - create an element for each of them
	 * 
	 * @param string $fieldName
	 * @param array|string $fieldValues
	 */
	public static function createDcField($fieldName, $fieldValues)
	{
		$nodes = array();
		$doc = new DOMDocument('1.0', 'utf-8');
	
		if (!is_array($fieldValues)) {
			$fieldValues = array($fieldValues);
		}
		
		foreach ($fieldValues as $fieldValue) {			
			$node = $doc->createElement('dc:' . str_ireplace('dcterms_', '', $fieldName));
			
			$node->setAttribute('xmlns:dc', self::$namespaces['dc']);
			
			$node->appendChild($doc->createTextNode($fieldValue));
			$nodes[] = $node;
		}
		return $nodes;
	}
	
	/**
	 * Creates dc xml element for a language field. $fieldValues must contain the values in all langs
	 *
	 * @param string $fieldName
	 * @param array $fieldValues An assoc array of type array("en" => value, "nl" => value).
	 */
	public static function createDcLanguageField($fieldName, $fieldValues)
	{
		$nodes = array();
		$doc = new DOMDocument('1.0', 'utf-8');
	
		if ( ! is_array($fieldValues)) {
			$fieldValues = array($fieldValues);
		}
		
		foreach ($fieldValues as $languageCode => $fieldValue) {			
			$node = $doc->createElement('dc:' . str_ireplace('dcterms_', '', $fieldName));
			
			$node->setAttribute('xmlns:dc', self::$namespaces['dc']);
			
			$node->setAttribute('xml:lang', $languageCode);
			
			$node->appendChild($doc->createTextNode($fieldValue));
			$nodes[] = $node;
		}
		return $nodes;
	}
    
    /**
	 * Validate if the scheme is unique in the given tenant. 
     * If not - checks if it should be perged. If not adds it to the _duplicateConceptSchemes and return false.
	 * 
	 * @param OpenSKOS_Solr_Document $schemeDoc
	 * @param int $byUserId, optional
     * @return bool If the document can be inserted
	 */
	protected function handleUniqueConceptScheme(OpenSKOS_Solr_Document $schemeDoc, $byUserId = null)
	{
        $canInsertDocument = true;
        
		$schemeUri = $schemeDoc->offsetGet('uri');
		$schemesCheck = $this->_solr()
            ->limit(1)
            ->search(
                'uri:"' . $schemeUri[0] . '" '
                . 'AND tenant:"' . $this->getOpt('tenant') . '" '
                . 'AND deleted:false '
                . 'AND collection:' . $this->getCollection()->id
            );
        
		if ($schemesCheck['response']['numFound'] > 0) {
            if (!$this->getOpt('purge')) {
                $this->_duplicateConceptSchemes[] = $schemeUri[0];
                $canInsertDocument = false;
            } else {
                $conceptScheme = new Editor_Models_ConceptScheme(
                    new Api_Models_Concept(
                        array_shift($schemesCheck['response']['docs'])
                    )
                );
                $conceptScheme->delete(true, $byUserId);
            }
		}
        
		return $canInsertDocument;
	}
	
    /**
	 * Validate if the concept has unique notation.
     * If not - checks if it should be perged. If not adds it to the _notImportedNotations and return false.
	 * 
     * @param array $data
     * @param DOMNodeList $notationNodes
     * @param array $notationsCheck
     * @return boolean If the document can be inserted
     */
	protected function handleUniqueNotation(array &$data, DOMNodeList $notationNodes, array $notationsCheck)
	{
        $canInsertDocument = true;
        
		if ($notationNodes->length > 0) {
            $notation = $notationNodes->item(0)->nodeValue;
            if (isset($notationsCheck[$notation])) {
                if ($this->getOpt('onlyNewConcepts')) {
                    $this->_notImportedNotations[] = $notation;
                    $canInsertDocument = false;
                } else {
                    $existingConcept = Api_Models_Concepts::factory()->getConcept(
                        $notationsCheck[$notation]
                    );

                    $existingConcept->purge();

                    $data['uuid'] = $existingConcept['uuid'];
                }
            }
        }
        
        return $canInsertDocument;
	}
    
    /**
     * Fetch existing notations and the uuid of the concepts having them.
     * @return array
     */
    protected function fetchNotationsCheck()
    {
        $notationsCheck = array();
        $notationsCheckQuery = 'class:Concept '
            . 'AND deleted:false '
            . 'AND tenant:' . $this->getOpt('tenant') . ' '
            . 'AND collection:' . $this->getCollection()->id;
        
		$notationsCount = $this->_solr()->limit(0)->search($notationsCheckQuery);
		$existingNotations = $this->_solr()->limit($notationsCount['response']['numFound'])->search($notationsCheckQuery, array('fl' => 'notation, uuid'));
		foreach ($existingNotations['response']['docs'] as $doc) {
			$notationsCheck[$doc['notation'][0]] = $doc['uuid'];
		}
        
        return $notationsCheck;
    }
}
