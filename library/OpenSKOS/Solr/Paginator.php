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

class OpenSKOS_Solr_Paginator implements Zend_Paginator_Adapter_Interface
{
    /**
     * Array
     *
     * @var array
     */
    protected $_solrResponse = null;

    /**
     * Item count
     *
     * @var integer
     */
    protected $_count = null;
    
    /**
     * 
     * @var OpenSKOS_Solr
     */
    protected $_solr;
    
    protected $_parameters, $_query;

    /**
     * Constructor.
     *
     * @param array $array Array to paginate
     */
    public function __construct($query, array $parameters = array())
    {
    	$this->_solr = OpenSKOS_Solr::getInstance();
    	$this->_parameters = $parameters;
    	$this->_query = $query;
    	$response = $this->_solr->search($this->_query, $this->_parameters + array('rows' => 0));
        $this->_count = $response['response']['numFound'];
    }

    /**
     * Returns an array of items for a page.
     *
     * @param  integer $offset Page offset
     * @param  integer $itemCountPerPage Number of items per page
     * @return array
     */
    public function getItems($offset, $itemCountPerPage)
    {
    	$response = $this->_solr->search($this->_query, $this->_parameters + array('start' => $offset, 'rows' => $itemCountPerPage));
    	return $response['response']['docs'];
    }

    /**
     * Returns the total number of rows in the array.
     *
     * @return integer
     */
    public function count()
    {
	    	$response = $this->_solr->search($this->_query, $this->_parameters + array('rows' => 0));
	        $this->_count = $response['response']['numFound'];
    	return $this->_count;
    }
}