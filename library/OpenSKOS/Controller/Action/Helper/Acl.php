<?php
/**
 *
 * @author mlindeman
 * @version 
 */
require_once 'Zend/Loader/PluginLoader.php';
require_once 'Zend/Controller/Action/Helper/Abstract.php';

/**
 * Acl Action Helper
 *
 * @uses actionHelper OpenSKOS_Action_Helper
 */
class OpenSKOS_Controller_Action_Helper_Acl extends Zend_Controller_Action_Helper_Abstract {
	/**
	 * Constructor: initialize plugin loader
	 *
	 * @return void
	 */
	public function __construct() 
	{
	}
	
	/**
	 * Strategy pattern: call helper as broker method
	 */
	public function direct() 
	{
		return Zend_Registry::get(OpenSKOS_Application_Resource_Acl::REGISTRY_KEY);
	}
}
