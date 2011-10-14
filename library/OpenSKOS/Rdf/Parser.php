<?php 
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
		'purge|P' => 'Purge all concepts per ConceptSchema found in this file',
		'lang|l=s' => 'The default language to use if no "xml:lang" attribute is found',
		'env|e=s' => 'The environment to use (defaults to "production")',
		'commit' => 'Commit to Solr (default: print to STDOUT)'
	);
	
	static $namespaces = array(
		'rdf'      => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
		'rdfs'     => 'http://www.w3.org/2000/01/rdf-schema#',
		'skos'     => 'http://www.w3.org/2004/02/skos/core#',
		'openskos' => 'http://openskos.org/xmlns#',
		'dc'       => 'http://purl.org/dc/elements/1.1/',
		'dcterms'  => 'http://purl.org/dc/terms/'
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
	
	const MAX_LIMIT = 1000;
	
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
		static $doc;
		if (null === $doc) {
			$file = $this->getFile();
			$doc = new DOMDocument('1.0', 'utf-8');
			if (!@$doc->load($file)) {
				throw new OpenSKOS_Rdf_Parser_Exception('Failed to load `'.$file.'` as DOMDocument');
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
		$parser = xml_parser_create_ns();
		xml_parser_set_option( $parser, XML_OPTION_SKIP_WHITE, 1 );
		xml_parser_set_option( $parser, XML_OPTION_CASE_FOLDING, 0);
		
		$helper = new OpenSKOS_Rdf_Parser_Helper($this);
		xml_set_element_handler($parser, array($helper, "startTag"), array($helper, "endTag"));
		xml_set_character_data_handler($parser, array($helper, "characterData")); 
		xml_set_start_namespace_decl_handler($parser, array($helper, "startNameSpaceDeclaration")); 
		xml_set_end_namespace_decl_handler($parser, array($helper, "endNameSpaceDeclaration")); 
		
		$fp = $this->_getFilePointer();
		while ($data = fread($fp, 4096)) {
			try {
				if (!xml_parse($parser, $data, feof($fp))) {
					throw new OpenSKOS_Rdf_Parser_Exception(sprintf("XML error: %s at line %d", xml_error_string(xml_get_error_code($parser)), xml_get_current_line_number($parser)));
				}
			} catch (OpenSKOS_Rdf_Parser_Helper_Exception $e) {
				//end reached, no problem
				break;
			}
		}
		fclose($fp);
		
		xml_parser_free($parser);
		
		if ($this->_opts->commit) {
			$solr = $this->_solr();
			$solr->add($helper);
		} else {
			echo $helper;
		}
		
//		var_dump($helper->getNamespaces());
		
		return $this;
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
	 * @return OpenSKOS_Solr_Document
	 */
	public static function DomNode2SolrDocument(
		DOMNode $Description, 
		Array $extradata = array(), 
		DOMXPath $xpath = null)
	{
		if ($Description->nodeName != 'rdf:Description') {
			throw new OpenSKOS_Rdf_Parser_Exception('wring nodeName, expected `rdf:Description`, got `'.$Description->nodeName.'`');
		}
		
		$document = new OpenSKOS_Solr_Document();
		foreach ($extradata as $key => $var) {
			$document->$key = $var;
		}
		
		$uri = $Description->getAttributeNS(self::$namespaces['rdf'], 'about');
		if (!$uri) {
			throw new OpenSKOS_Rdf_Parser_Exception('missing required attribute rdf:about');
		}
		
		$document->uri = $uri;
		$document->uuid = self::uri2uuid($uri);
		
		if (null === $xpath) {
			$xpath = new DOMXPath($Description->ownerDocument);
			//support for only these namespaces:
			foreach (self::$namespaces as $prefix => $uri) {
				$xpath->registerNamespace($prefix, $uri);
			}
		} 
		
		if ($type = ($xpath->query('./rdf:type', $Description)->item(0))) {
			$resource = $type->getAttributeNS(self::$namespaces['rdf'], 'resource');
			if (0!==strpos($resource, self::$namespaces['skos'])) {
				return;
			}
			$className = parse_url($resource, PHP_URL_FRAGMENT);
			$document->class =parse_url($type->getAttributeNS(self::$namespaces['rdf'], 'resource'), PHP_URL_FRAGMENT);
		} else {
			throw new OpenSKOS_Rdf_Parser_Exception('missing required attribute rdf:type');
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
				? $skosElement->nodeValue
				: $skosElement->getAttributeNS(self::$namespaces['rdf'], 'resource');
		}
		
		foreach (array('dc', 'dcterms') as $ns) {
			foreach ($xpath->query('./'.$ns.':*', $Description) as $element) {
				$fieldname = str_replace($ns.':', 'dcterms_', $element->nodeName);
				$document->$fieldname = $element->nodeValue;
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
			$document->$fieldname = $element->nodeValue;
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
	
	public function process()
	{
		$xpath = new DOMXPath($this->getDOMDocument());
		//support for only these namespaces:
		foreach (self::$namespaces as $prefix => $uri) {
			$xpath->registerNamespace($prefix, $uri);
		}

		$Descriptions = $xpath->query('/rdf:RDF/rdf:Description');
		
		//store all Namespaces used by this scheme in Database:
		$namespaces = self::getDocNamespaces($this->getDOMDocument());
		$this->getCollection()->setNamespaces($namespaces);

		$addDoc = new DOMDocument('1.0', 'utf-8');
		$addDoc->appendChild($addDoc->createElement('add'));
		$documents = new OpenSKOS_Solr_Documents();
		
		$d = 0;
		foreach ($Descriptions as $i => $Description) {
			if ($i < $this->getFrom()) continue;
//			if ($i >= ($this->getFrom() + $this->getLimit())) break;
			
			if ($d >= self::MAX_LIMIT) {
				$this->_solr()->add($documents);
				$documents = new OpenSKOS_Solr_Documents();
				$d = 0;
			}
			$d++;
			
			$data = array(
				'tenant' => $this->getOpt('tenant'),
				'collection' => $this->_collection->id,
			);
			$document = self::DomNode2SolrDocument($Description, $data);
			if ($document) {
				$documents->add($document);
			}
		}
		
		if (null!==$this->getOpt('commit')) {
			$this->_solr()->add($documents);
		} else {
			echo $documents."\n";
		}
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
			throw new OpenSKOS_Rdf_Parser_Exception('[ options ]', '[ options ] file', $this->_opts->getUsageMessage());
		}
		$this->setFiles($files);
		
		$model = new OpenSKOS_Db_Table_Tenants();
		$tenant = $model->find($opts->tenant)->current();
		if (null === $tenant) {
			throw new OpenSKOS_Rdf_Parser_Exception("No such tenant: `{$opts->tenant}`");
		}
		
		$model = new OpenSKOS_Db_Table_Collections();
		$collection = $model->findByCode($opts->collection, $opts->tenant);
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
			if ($this->_opts->env) define('APPLICATION_ENV', $this->_opts->env);
			//bootstrap the application:
			include dirname(__FILE__) . '/../../../public/index.php';
			error_reporting(E_ALL);
			ini_set('display_errors', true);
			$firstRun = false;
		}
		return $this;
	}
	
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
}


