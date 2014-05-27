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

class Api_AutocompleteController extends OpenSKOS_Rest_Controller {

	/**
	 * 
	 * @var Concepts
	 */
	protected $model;
	
	public function init()
	{
		$this->model = Api_Models_Concepts::factory()->setQueryParams(
			$this->getRequest()->getParams()
		);
		parent::init();
		$this->_helper->contextSwitch()
			->initContext($this->getRequest()->getParam('format', 'json'));
		$this->view->setEncoding('UTF-8');
	}
	
	public function indexAction() {
		if (null === ($q = $this->getRequest()->getParam('q'))) {
			$this->getResponse()
				->setHeader('X-Error-Msg', 'Missing required parameter `q`');
			throw new Zend_Controller_Exception('Missing required parameter `q`', 400);
		}
		$this->_helper->contextSwitch()->setAutoJsonSerialization(false);
		$this->getResponse()->setBody(
                    json_encode($this->model->getConcepts($q, null, true))
                );
	}

	public function getAction() {
		$this->_helper->contextSwitch()->setAutoJsonSerialization(false);
                $this->getResponse()->setBody(
                    json_encode($this->model->autocomplete($this->getRequest()->getParam('id')))
                );
	}

	public function postAction() {
		$this->_501('POST');
	}

	public function putAction() {
		$this->_501('POST');
	}

	public function deleteAction() {
		$this->_501('DELETE');
	}

}

