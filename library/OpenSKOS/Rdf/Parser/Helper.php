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

class OpenSKOS_Rdf_Parser_Helper
{
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
	
	protected $_counter = 0;
	
	/**
	 * 
	 * @var OpenSKOS_Rdf_Parser
	 */
	protected $_rdf_parser;
	
	public static $standardNamespaces = array('dc', 'dcterms', 'rdfs');
	
	protected $_namespaces = array();
	
	protected $_xml;
	
	public function __construct(OpenSKOS_Rdf_Parser $rdf_parser)
	{
		$this->_rdf_parser = $rdf_parser;
		$this->_xml = '<add>';
	}
	
	public function getNamespaces()
	{
		return $this->_namespaces;
	}
	
	protected function _close()
	{
		static $closed;
		if (null === $closed) {
			$this->_xml .="\n</add>";
			$closed = true;
		}
		return $this;
	}
	
	public function __toString()
	{
		$this->_close();
		return $this->_xml;
	}
	
	public function characterData($parser, $data)
	{
		if (
			substr($this->_xml, -2) != '/>' 
			&& substr($this->_xml, -3) != ']]>' 
			&& substr($this->_xml, -1) == '>'
			&& substr($this->_xml, -5) != '<doc>'
			&& substr($this->_xml, -5) != '<add>'
			&& substr($this->_xml, -8) != '</field>'
			&& trim($data)
		) {
			$this->_xml .= "<![CDATA[".$data."]]>";
		}
	} 
	
	public function startTag($parser, $name, $attributes)
	{
		$this->_parseElementName($name, $nsPrefix, $nsUri, $tagName);
		
		if ($tagName == 'RDF' && $nsPrefix == 'rdf') {
			return;
		}
		
		if ($this->_counter < $this->_rdf_parser->getFrom()) {
			return;
		}
		
		if ($tagName == 'Description' && $nsPrefix == 'rdf') {
			//start of a document:
			$this->_xml .= "\n  <doc>";
			
			$uri = $attributes['http://www.w3.org/1999/02/22-rdf-syntax-ns#:about'];
			//metafields:
			$this->_xml .= "\n    <field name=\"tenant\">".$this->_rdf_parser->getOpt('tenant')."</field>";
			$this->_xml .= "\n    <field name=\"collection\">".$this->_rdf_parser->getOpt('collection')."</field>";
			$this->_xml .= "\n    <field name=\"uri\">{$uri}</field>";
			$this->_xml .= "\n    <field name=\"uuid\">".self::uri2uuid($uri)."</field>";
			
			//get the entire XML structure:
			$doc = $this->_rdf_parser->getDOMDocument();
			$node = $doc->getElementsByTagName('Description')->item($this->_counter);
			$xml = $doc->saveXml($node);
			$this->_xml .= "\n    <field name=\"xml\"><![CDATA[".$xml."]]></field>";
			return;
		}
		
		if ($nsPrefix == 'skos') {
			//is this a language enabled SKOS class?
			$fieldname = $tagName;
			if (in_array($tagName, self::$langMapping)) {
				if (isset($attributes['http://www.w3.org/XML/1998/namespace:lang'])) {
					$fieldname = $tagName . '@'.$attributes['http://www.w3.org/XML/1998/namespace:lang'];
				}
			}
			$this->_xml .= "\n    <field name=\"{$fieldname}\">";
			if (isset($attributes['http://www.w3.org/1999/02/22-rdf-syntax-ns#:resource'])) {
				$this->_xml.=$attributes['http://www.w3.org/1999/02/22-rdf-syntax-ns#:resource'];
				$this->_xml .= '</field>';
			}
		} elseif (in_array($nsPrefix, self::$standardNamespaces)) {
		}
	}
	
	public function getNsPrefixByUri($nsUri) 
	{
		return array_search($nsUri, $this->getNamespaces());
	}
	
	protected function _parseElementName($name, &$nsPrefix, &$nsUri, &$tagName)
	{
		$pairs = preg_split('/^(.+)\:([A-Za-z0-9\-_]+)$/', $name, 2, PREG_SPLIT_DELIM_CAPTURE);
		$nsUri = $pairs[1];
		$tagName = $pairs[2];
		$nsPrefix = $this->getNsPrefixByUri($nsUri);
	}
	
	public function endTag($parser, $name)
	{
		$this->_parseElementName($name, $nsPrefix, $nsUri, $tagName);

		if ($tagName == 'Description' && $nsPrefix == 'rdf') {
			//end of a document:
			$this->_counter++;
			if ($this->_counter <= $this->_rdf_parser->getFrom()) return;
			$this->_xml .= "\n  </doc>";
			if ($this->_counter >= $this->_rdf_parser->getFrom() + $this->_rdf_parser->getLimit()) {
				throw new OpenSKOS_Rdf_Parser_Helper_Exception($this->_counter . ' documents processed');
			}
			return;
		}
		if (substr($this->_xml, -3) == ']]>') $this->_xml .= "</field>";
	}
	
	function startNamespaceDeclaration($parser, $prefix, $uri)
	{
		$this->_namespaces[$prefix] = $uri;
	}
	
	function endNamespaceDeclaration($parser, $prefix)
	{
		
	}
	
	public static function uri2uuid($uri)
	{
		$hash = md5($uri);
		return substr($hash, 0, 8) . '-' . substr($hash, 8, 4) 
			. '-' . substr($hash, 12, 4)
			. '-' . substr($hash, 16, 4)
			. '-' . substr($hash, 20);
	}
	
}


class OpenSKOS_Rdf_Parser_Helper_End_Reached_Exception extends Exception
{
	
}


