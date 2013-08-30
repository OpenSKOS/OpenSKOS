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

class OpenSKOS_Solr_Document implements Countable, ArrayAccess, Iterator
{
	protected $fieldnames = array();
	protected $data = array();
	protected $position = 0;
	
	public function __set($fieldname, $value)
	{
	    //this differs from self::offsetSet:
	    // - if a field has been set before, make this field multiValued
	    $offsetExists = $this->offsetExists($fieldname);
	    $value = !is_array($value) ? array($value) : $value;
	    if (!$offsetExists) {
	        $this->fieldnames[] = $fieldname;
	        $this->data[$fieldname] = $value;
	    } else {
	        if (is_array($this->data[$fieldname])) {
	            $this->data[$fieldname] = array_merge($this->data[$fieldname], $value);
	        } else {
	            $this->data[$fieldname] = array_merge(array($this->data[$fieldname]), $value);
	        }
	    }
	}
	
	public function offsetSet($fieldname, $value) {
		$newField = false;
		if (!$this->offsetExists($fieldname)) {
			$this->fieldnames[] = $fieldname;
			$newField = true;
		}
		if (!is_array($value)) {
			if (false === $newField) {
				$this->data[$fieldname][] = $value;
			} else {
				$this->data[$fieldname] = array($value);
			}
		} else {
			if (false === $newField) {
				$this->data[$fieldname] = $this->data[$fieldname] + $value;
			} else {
				$this->data[$fieldname] = $value;
			}
		}
	}
	
	public function offsetExists($fieldname) {
		return in_array($fieldname, $this->fieldnames);
	}
	
	public function offsetUnset($fieldname) {
		if (!$this->offsetExists($fieldname)) {
			trigger_error('Undefined index: '.$fieldname, E_USER_NOTICE);
			return;
		}
		unset ( $this->data[$fieldname]);
		$ix = array_search($fieldname, $this->fieldnames);
		unset($this->fieldnames[$ix]);
		$fieldnames = array();
		foreach ($this->fieldnames as $fieldname) $fieldnames[] = $fieldname;
		$this->fieldnames = $fieldnames;
		$this->rewind();
	}
	
	public function offsetGet($fieldname) {
		return $this->offsetExists($fieldname) ? $this->data [$fieldname] : null;
	}
	
	public function count()
	{
		return count($this->fieldnames);
	}
	
    public function rewind() {
    	$this->position = 0;
    }

    public function current() {
        return $this->data[$this->fieldnames[$this->position]];
    }

    public function key() {
    	return $this->fieldnames[$this->position];
    }

    public function next() {
    	++$this->position;
    }

    public function valid() {
    	return isset($this->data[$this->fieldnames[$this->position]]);
    }
    
    public function toArray()
    {
    	return $this->data;
    }
    
	/**
	 * @return OpenSKOS_Solr
	 */
	protected function solr()
	{
		return Zend_Registry::get('OpenSKOS_Solr');
	}
	
    public function save($commit = null)
    {
    	$this->solr()->add(new OpenSKOS_Solr_Documents($this), $commit);
    	return $this;
    }
    
    /**
     * Registers the notation of the document in the database, or generates one if the document does not have notation.
     * 
     * @return OpenSKOS_Solr
     */
    public function registerOrGenerateNotation()
    {   
    	if ((isset($this->data['class']) && $this->data['class'] == 'Concept')
    			|| (isset($this->data['class'][0]) && $this->data['class'][0] == 'Concept')) {
    		
    		$currentNotation = '';
	    	if (isset($this->data['notation']) && isset($this->data['notation'][0])) {
	    		$currentNotation = $this->data['notation'][0];
	    	}
	    	
	    	if (empty($currentNotation)) {
	    		$this->fieldnames[] = 'notation';
	    		$this->data['notation'] = array(OpenSKOS_Db_Table_Notations::getNext());
	    		
	    		// Adds the notation to the xml. At the end just before </rdf:Description>
	    		$closingTag = '</rdf:Description>';
	    		$notationTag = '<skos:notation>' . $this->data['notation'][0] . '</skos:notation>';
	    		$xml = $this->data['xml'];
	    		$xml = str_replace($closingTag, $notationTag . $closingTag, $xml);
	    		$this->data['xml'] = $xml;
	    		
	    	} else {
	    		if ( ! OpenSKOS_Db_Table_Notations::isRegistered($currentNotation)) {
	    			// If we do not have the notation registered - register it.
	    			OpenSKOS_Db_Table_Notations::register($currentNotation);
	    		}
	    	}
    	}
    	
    	return $this;
    } 
        
    public function __toString()
    {
    	$doc = new DOMDocument();
    	$doc->loadXML('<doc/>');
    	foreach ($this->fieldnames as $fieldname) {
    		foreach ($this->data[$fieldname] as $value) {
    			$node = $doc->documentElement->appendChild($doc->createElement('field'));
    			$htmlSafeValue = htmlspecialchars($value);
    			if ($htmlSafeValue == $value) {
	    			$node->appendChild($doc->createTextNode($htmlSafeValue));
    			} else {
    				$node->appendChild($doc->createCDataSection($value));
    			}
    			$node->setAttribute('name', $fieldname);
    		}
    	}
    	return $doc->saveXml($doc->documentElement);
    }
}