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

abstract class OpenSKOS_SKOS_Docs_Abstract implements ArrayAccess
{
	/**
	 * @var $_data array
	 */
	protected $_data;
	
	/**
	 * 
	 * @var DOMDocument
	 */
	protected $_DomDocument;
	
	/**
	 * 
	 * @var XPath
	 */
	protected $_DOMXPath;
	
	/**
	 * 
	 * @var SimpleXMLElement
	 */
	protected $_SimpleXMLElement;
	
	public function __construct(array $solrDoc = array())
	{
		if (null !== $solrDoc) {
			$this->loadFromSolrDoc($solrDoc);
		}
	}
	
	/**
	 * 
	 * @param $solrDoc array
	 * @return OpenSKOS_SKOS_Docs_Abstract
	 */
	public function loadFromSolrDoc(array $solrDoc)
	{
		$this->_data = $solrDoc;
		return $this;
	}
	
	/**
	 * @param offset
	 */
	public function offsetExists ($offset) 
	{
		return isset($this->_data[$offset]);
	}

	/**
	 * @param offset
	 */
	public function offsetGet ($offset) 
	{
        if (0===strpos($offset, 'dc_')) {
            $name = str_replace('dc_', '', $offset);
            foreach ($this->getSimpleXMLElement()->children('http://purl.org/dc/elements/1.1/') as $child) {
                if($child->getName()==$name) {
                    return $child;
                }
            }
            return $this->getSimpleXMLElement()->{str_replace('dc_', '', $offset)};
        } else {
    	    return $this->offsetExists($offset) ? $this->_data[$offset] : null;
        }
	}

	/**
	 * @param offset
	 * @param value
	 * @return OpenSKOS_SKOS_Docs_Abstract
	 */
	public function offsetSet ($offset, $value) 
	{
		if (null === $offset) {
			throw new OpenSKOS_SKOS_Exception('You can only set data ass associative array');
		}
		$this->_data[$offset] = $value;
		return $this;
	}

	/**
	 * @param offset
	 * @return OpenSKOS_SKOS_Docs_Abstract
	 */
	public function offsetUnset ($offset) 
	{
		unset($this->_data[$offset]);
		return $this;
	}
	
	/**
	 * Get a DOMDocument from the internal XML data
	 * 
	 * @throws OpenSKOS_SKOS_Exception
	 * @return DOMDocument
	 */
	public function getDomDocument()
	{
	    if (null === $this->_DomDocument && $this['xml']) {
	        $DomDocument = new DOMDocument('1.0', 'utf-8');
	        if (!@$DomDocument->loadXML($this['xml'])) {
	            throw new OpenSKOS_SKOS_Exception('Failed to load XML as a DOMDocument');
	        }
	        $this->_DomDocument = &$DomDocument;
	    }
	    return $this->_DomDocument;
	}
	
	/**
	 * Get a SimpleXML from the internal XML data
	 * 
	 * @throws OpenSKOS_SKOS_Exception
	 * @return SimpleXMLElement
	 */
	public function getSimpleXMLElement()
	{
	    if (null === $this->_SimpleXMLElement && $this['xml']) {
	        try {
    	        $SimpleXMLElement = @new SimpleXMLElement($this['xml']);
	        } catch (Exception $e) {
	            throw new OpenSKOS_SKOS_Exception('Failed to load XML as a SimpleXMLElement');
	        }
	        $this->_SimpleXMLElement = &$SimpleXMLElement;
	    }
	    return $this->_SimpleXMLElement;
	}
	
	/**
	 * @return DOMXPath
	 */
	public function getXPath()
	{
	    if (null === $this->_DOMXPath && null !== ($doc = $this->getDomDocument())) {
	         $this->_DOMXPath = new DOMXPath($doc);
	    }
	    return $this->_DOMXPath;
	}
	
}