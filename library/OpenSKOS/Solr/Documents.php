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

class OpenSKOS_Solr_Documents implements Countable, Iterator
{
	protected $documents = array();
	protected $position = 0;
	
	public function __construct(OpenSKOS_Solr_Document $document = null)
	{
		if (null !== $document) {
			$this->add($document);
		}
	}

	public function count()
	{
		return count($this->documents);
	}
	
    public function rewind() {
    	$this->position = 0;
    }

    public function current() {
        return $this->documents[$this->position];
    }

    public function key() {
    	return $this->position;
    }

    public function next() {
    	++$this->position;
    }

    public function valid() {
    	return isset($this->documents[$this->position]);
    }
    
    public function add(OpenSKOS_Solr_Document $document)
    {
    	$this->documents[] = $document;
    	return $this;
    }
    
    public function __toString()
    {
    	$doc = new DOMDocument();
    	$doc->loadXML('<add/>');
    	foreach ($this->documents as $document) {
    		$frag = $doc->createDocumentFragment();
    		$frag->appendXml((string)$document);
    		$doc->documentElement->appendChild($frag);
    	}
    	return $doc->saveXml($doc->documentElement);
    }
}