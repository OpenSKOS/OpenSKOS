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

class Api_SolrSchemaController extends OpenSKOS_Rest_Controller {

	public function init()
	{
		parent::init();
		$this->_helper->contextSwitch()
			->initContext('xml');
		Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer')->setNoRender(true);
		$this->getHelper('layout')->disableLayout();
	}
	
	public function indexAction() 
	{
		echo Zend_Registry::get('OpenSKOS_Solr')->getSchema(false);
	}

	public function getAction() {
		$this->_501('GET');
	}

	public function postAction() {
		$this->_501('POST');
	}

	public function putAction() {
		$this->_501('PUT');
	}

	public function deleteAction() {
		$this->_501('DELETE');
	}

}

