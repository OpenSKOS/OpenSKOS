<?php

class OpenSKOS_Oai_Pmh_Harvester_Items_MetadataFormats extends DOMDocument
{
	public function __construct(DOMNode $node)
	{
		$this->loadXML($node->ownerDocument->saveXml($node));
	}
	
	public function __get($key) 
	{
		$node  = $this->getElementsByTagName($key)->item(0);
		if (null !== $node && $node->nodeType == XML_ELEMENT_NODE) {
			return $node->nodeValue;
		}
	}
	
	public function __toString()
	{
		return $this->saveXml($this->documentElement);
	}
}
