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

class OpenSKOS_Oai_Pmh_Harvester implements Iterator, Countable
{
	/**
	 * @param OpenSKOS_Db_Table_Row_Collection $collection
	 */
	protected $_collection;
	
	protected $_page = 0, $_lastPage = false;
	
	/**
	 * @var $_client Zend_Http_Client
	 */
	protected $_client;
	
	/**
	 * @var $_records OpenSKOS_Oai_Pmh_Harvester_Records
	 */
	protected $_records;
	
	protected $_resumptionToken;
	
	protected $_options = array('metadataPrefix' => 'oai_rdf');
	
	/**
	 * @param OpenSKOS_Db_Table_Row_Collection $collection
	 */
	public function __construct(OpenSKOS_Db_Table_Row_Collection $collection)
	{
		$this->_collection = $collection;
		if (!$this->_collection->OAI_baseURL) {
		    throw new OpenSKOS_Oai_Pmh_Harvester_Exception("Collection has no OAI base URL");
		}
		//load options from URI:
		$query = array();
		parse_str(parse_url($this->_collection->OAI_baseURL, PHP_URL_QUERY), $query);
		foreach ($query as $key => $val) {
		    $this->setOption($key, $val);
		}
	}
	
	/**
	 * 
	 * @param OpenSKOS_Db_Table_Row_Collection $collection
	 * @return OpenSKOS_Oai_Pmh_Harvester
	 */
	public static function factory(OpenSKOS_Db_Table_Row_Collection $collection)
	{
		$className = __CLASS__;
		return new $className($collection);
	}
	
	/**
	 * 
	 * @param array $options
	 * @return OpenSKOS_Oai_Pmh_Harvester
	 */
	public function setOptions(Array $options)
	{
		$this->_options = $options;
		return $this;
	}
	
	/**
	 * 
	 * @param string $key
	 * @param mixed $value
	 * @return OpenSKOS_Oai_Pmh_Harvester
	 */
	public function setOption($key, $value)
	{
		if (null === $value && isset($this->_options[$key])) {
			unset($this->_options[$key]);
		} else {
			$this->_options[$key] = $value;
		}
		return $this;
	}
	
	/**
	 * Get SetSpecs from the repository
	 * 
	 * @return OpenSKOS_Oai_Pmh_Harvester_Sets
	 */
	public function listSets()
	{
		$response = $this->_getClient()
			->setParameterGet(array(
				'verb' => 'ListSets'
			))->request('GET');
		if ($response->isError()) {
			throw new OpenSKOS_Oai_Pmh_Harvester_Exception($response->getMessage());
		}
		
		return new OpenSKOS_Oai_Pmh_Harvester_Sets($response);
	}
	
	public static function getOaiDate($ts) 
	{
		if (!is_int($ts)) {
			throw new OpenSKOS_Oai_Pmh_Harvester_Exception('Expected a timestamp');
		}
		return date('Y-m-d\TH:i:sZ', $ts);
	}
	
	/**
	 * @return OpenSKOS_Oai_Pmh_Harvester
	 */
	public function setFrom($ts)
	{
		if (null!==$ts && !is_int($ts)) {
			throw new OpenSKOS_Oai_Pmh_Harvester_Exception('Expected a timestamp');
		}
		
		if (null!==$ts) {
			$from = self::getOaiDate($ts);
		} else {
			$from = null;
		}
		$this->setOption('from', $from);
		return $this;
	}
	
	/**
	 * @return OpenSKOS_Oai_Pmh_Harvester
	 */
	public function setUntil($ts)
	{
		if (null!==$ts && !is_int($ts)) {
			throw new OpenSKOS_Oai_Pmh_Harvester_Exception('Expected a timestamp');
		}
		
		if (null!==$ts) {
			$until = self::getOaiDate($ts);
		} else {
			$until = null;
		}
		$this->setOption('until', $until);
		return $this;
	}
	
	/**
	 * @return Zend_Http_Client
	 */
	protected function _getClient()
	{
		if (null === $this->_client) {
			$this->_client = new Zend_Http_Client($this->_collection->OAI_baseURL, array(
				'maxredirects' => 0,
				'timeout' => 60
			));
		}
		return $this->_client;
	}
	
	public function getOption($key, $default = null) 
	{
		return isset($this->_options[$key]) ? $this->_options[$key] : $default;
	}
	
	/**
	 * @return OpenSKOS_Oai_Pmh_Harvester_Records
	 */
	public function current () 
	{
		return $this->_records;
	}

	public function next () 
	{
		++$this->_page;
		$resumptionToken = $this->_records->getResumptionToken();
		if (null === $resumptionToken) {
			$this->_lastPage = true;
		} else {
			//load the next set of records, using the resumptiontiontoken:
			$this->_loadRecords($resumptionToken);
		}
	}

	/**
	 * @return int
	 */
	public function key () 
	{
		return $this->_page;
	}

	/**
	 * @return bool
	 */
	public function valid () 
	{
	    return 
			($this->key() == 0  || $this->_lastPage == false)
			&& count($this->_records);
	}
	
	public function count()
	{
	    return $this->valid() ? count($this->_records) : null;
	}
	
	/**
	 * @param string $resumptionToken
	 * @return OpenSKOS_Oai_Pmh_Harvester
	 */
	protected function _loadRecords($resumptionToken = null)
	{
		$client = $this->_getClient();
		$params = array(
			'verb' => 'ListRecords',
		);
		
		if (null!==$resumptionToken) {
			$params['resumptionToken'] = $resumptionToken;
		} else {
			$params += $this->_options;
		}
		$response = $client->setParameterGet($params)->request('GET');
		if ($response->isError()) {
			throw new OpenSKOS_Oai_Pmh_Harvester_Exception($response->getMessage());
		}
		
		$this->_records = new OpenSKOS_Oai_Pmh_Harvester_Records($response);
		return $this;
	}

	public function rewind () 
	{
	    $this->_loadRecords();
	}
}
