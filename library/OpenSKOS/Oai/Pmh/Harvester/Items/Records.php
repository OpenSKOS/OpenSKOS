<?php
class OpenSKOS_Oai_Pmh_Harvester_Items_Records extends DOMDocument
{
	/**
	 * @var SimpleXMLElement
	 */
	protected $_header;
	
	public function __construct(DOMNode $node)
	{
		$this->loadXML($node->ownerDocument->saveXml($node));
		$headerXml = $this->saveXml($this->getElementsByTagName('header')->item(0));
		$this->_header = new SimpleXMLElement($headerXml);
	}
	
	/**
	 * @return SimpleXMLElement
	 */
	public function getHeader()
	{
		return $this->_header;
	}
	
	public function __get($nodeName)
	{
		return $this->getHeader()->$nodeName;
	}
	
	/**
	 * Return the first node who's type = XML_ELEMENT_NODE
	 */
	public function metadata()
	{
		foreach ($this->getElementsByTagName('metadata')->item(0)->childNodes as $childNode) {
			if ($childNode->nodeType === XML_ELEMENT_NODE) {
				return $childNode;
			}
		}
	}
	
	public function __toString()
	{
		return $this->saveXml($this->metadata());
	}
}
