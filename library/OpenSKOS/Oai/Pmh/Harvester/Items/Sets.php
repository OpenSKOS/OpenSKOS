<?php

class OpenSKOS_Oai_Pmh_Harvester_Items_Sets extends DOMDocument
{
	public function __construct(DOMNode $node)
	{
		$this->loadXML($node->ownerDocument->saveXml($node));
	}
	
	public function __get($key) 
	{
		$node  = $this->getElementsByTagName($key)->item(0);
		if (null !== $node) {
			switch ($key) {
				case 'setSpec':
				case 'setName':
					return $node->nodeValue;
					break;
				case 'setDescription':
					foreach ($node->childNodes as $childNode) {
						if ($childNode->nodeType === XML_ELEMENT_NODE) {
							return $childNode;
						}
					}
					break;
			}
		}
	}
	
	public function __toString()
	{
		return $this->saveXml($this->metadata());
	}
}
