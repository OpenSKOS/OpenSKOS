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

class OpenSKOS_SKOS_Abstract implements Countable, Iterator
{
	protected $_doc = 0;
	
	protected $_docs;
	
	protected $_docClassName;
	
	public function __construct(Array $response)
	{
		if (null === $this->_docClassName) {
			throw new OpenSKOS_SKOS_Exception('No classname set');
		}
		$className = 'OpenSKOS_SKOS_Docs_' . ucfirst($this->_docClassName);
		foreach ($response['response']['docs'] as $doc)
		{
			$this->_docs[] = new $className($doc);
		}
	}
	
	public function count()
	{
		return count($this->_docs);
	}
	
	public function current() 
	{
		return $this->_docs[$this->_doc];
	}

	public function next() 
	{
		$this->_doc++;
	}

	public function key()
	{
		return $this->_doc;
	}

	public function valid() 
	{
		return isset($this->_docs[$this->_doc]);
	}

	public function rewind() 
	{
		$this->_doc = 0;
	}

}