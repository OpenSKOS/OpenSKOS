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
 * @copyright  Copyright (c) 2012 Pictura Database Publishing. (http://www.pictura-dp.nl)
 * @author     Alexandar Mitsev
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 */


class OpenSKOS_Http_Client_Api extends Zend_Http_Client {
	
	/**
	 * Holds the url for calls to the api.
	 * 
	 * @var string
	 */
	protected $_baseApiUrl;
		
	public function __construct($baseApiUrl)
	{
		parent::__construct();
		
		$this->_baseApiUrl = $baseApiUrl;
		$this->assignApiKey()
		->assignTenant();
	}
	
	/**
	 * Sets the action for which a request will be sent.
	 * This also sets the uri.
	 * 
	 * @param string $action
	 * @return OpenSKOS_Http_Client_Api
	 */
	public function setAction($action) {
		$this->setUri($this->_baseApiUrl . $action);
		return $this;
	}
	
	/**
	 * Makes a request to the api. Requests json format and returns the decoded json.
	 * 
	 * @throws OpenSKOS_Http_Client_Api_Exception
	 * @return array Decoded json response (as assoc array)
	 */
	public function defaultRequest() {
		$this->setParameterGet('format', 'json');
		$response = $this->request();
		if ($response->isError()) {
			throw new OpenSKOS_Http_Client_Api_Exception('No response from the api. At "' . $this->getUri() . '".');
		}
		return json_decode($response->getBody(), true);
	}
	
	/**
	 * Sets the api key parameter for the api requests.
	 * 
	 * @return OpenSKOS_Http_Client_Api
	 */
	protected function assignApiKey() {
		$user = OpenSKOS_Db_Table_Users::fromIdentity();
		if (null === $user) {
			throw new OpenSKOS_Http_Client_Api_Exception('User not found. Needed for request to the api.');
		}
		$this->setParameterGet('key', $user->apikey);
		return $this;
	}
	
	/**
	 * Sets the tenant parameter for the api requests.
	 * 
	 * @return OpenSKOS_Http_Client_Api
	 */
	protected function assignTenant() {
	    $tenant = OpenSKOS_Db_Table_Tenants::fromIdentity();
		if (null === $tenant) {
			throw new OpenSKOS_Http_Client_Api_Exception('Tenant not found. Needed for request to the api.');
		}
		$this->setParameterGet('tenant', $tenant->code);
		return $this;
	}
}

class OpenSKOS_Http_Client_Api_Exception extends Zend_Exception {
	
}