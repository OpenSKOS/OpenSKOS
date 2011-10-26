<?php
/**
 *
 * @author mlindeman
 * @version 
 */
require_once 'Zend/Loader/PluginLoader.php';
require_once 'Zend/Controller/Action/Helper/Abstract.php';

/**
 * Translate Action Helper 
 * 
 * @uses actionHelper OpenSKOS_Controller_Action_Helper
 */
class OpenSKOS_Controller_Action_Helper_Translate extends Zend_Controller_Action_Helper_Abstract {

	/**
	 * Strategy pattern: call helper as broker method
	 */
	public function translate($msgid) {
		try {
			return Zend_Registry::get('Zend_Translates')->translate($msgid);
		} catch (Zend_Exception $e) {
			return $msgid;
		}
	}
}

