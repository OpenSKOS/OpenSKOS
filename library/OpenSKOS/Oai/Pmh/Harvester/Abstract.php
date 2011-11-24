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

abstract class OpenSKOS_Oai_Pmh_Harvester_Abstract implements Iterator, Countable
{
	/**
	 * @var $_xpath DOMXPath
	 */
	protected $_xpath;
	
	protected $_key= 0;
	
	protected $_namespaces = array();
	
	/**
	 * @var $_nodeList DOMNodeList
	 */
	protected $_nodeList;
	
	public function __construct (Zend_Http_Response $response)
	{
		$doc = new DOMDocument();
		if (!@$doc->loadXml($response->getBody())) {
			throw new OpenSKOS_Oai_Pmh_Harvester_Exception('Failed to load XML from responseBody');
		}
		
		if ($doc->documentElement->nodeName != 'OAI-PMH') {
		    throw new OpenSKOS_Oai_Pmh_Harvester_Exception('XML response does not appear to be a valid OAI-PMH document.');
		}
		
		$this->_xpath = new DOMXPath($doc);
		$this->_xpath->registerNamespace('oai', 'http://www.openarchives.org/OAI/2.0/');
		
		foreach ($this->_namespaces as $prefix => $namespace) {
			$this->_xpath->registerNamespace($prefix, $namespace);
		}
		
		$errors = $this->_xpath->query('/oai:OAI-PMH/oai:error');
		if ($errors->length) {
			throw new OpenSKOS_Oai_Pmh_Harvester_Exception($errors->item(0)->nodeValue);
		}

		$this->_nodeList = $this->_xpath->query($this->_getXpathQuery());
	}
	
	protected abstract function _getXpathQuery();
	public abstract function toArray();
	
	
	protected function _subClassName()
	{
		$parts = explode('_', get_class($this));
		return ucfirst(array_pop($parts));
	}
	
	public function __call($name, $arguments)
	{
		$magicMethod = 'get'.$this->_subClassName();
		if ($name == $magicMethod) {
			return $this->getNodes();
		} else {
			throw new OpenSKOS_Oai_Pmh_Harvester_Exception($name.': no such method');
		}
	}
	
	/**
	 * @return DOMNodeList
	 */
	public function getNodes()
	{
		return $this->_nodeList;
	}
	
	public function count()
	{
		return $this->getNodes()->length;
	}
	
	public function current () 
	{
		$className = 'OpenSKOS_Oai_Pmh_Harvester_Items_' . $this->_subClassName();
		return new $className(
			$this->getNodes()->item($this->key())
		);
	}

	public function next () 
	{
		++$this->_key;
	}

	public function key () 
	{
		return $this->_key;
	}

	public function valid () 
	{
		return null !== $this->getNodes()->item($this->key());
	}

	public function rewind () 
	{
		$this->_key = 0;
	}
}
