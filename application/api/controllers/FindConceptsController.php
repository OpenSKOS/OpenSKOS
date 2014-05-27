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

class Api_FindConceptsController extends OpenSKOS_Rest_Controller {

	/**
	 * 
	 * @var Api_Models_Concepts
	 */
	protected $model;
	
	public function init()
	{
		parent::init();
		$this->model = Api_Models_Concepts::factory()->setQueryParams(
			$this->getRequest()->getParams()
		);
		$this->_helper->contextSwitch()
			->initContext($this->getRequest()->getParam('format', 'rdf'));
		
		if('html' == $this->_helper->contextSwitch()->getCurrentContext()) {
			//enable layout:
			$this->getHelper('layout')->enableLayout();
		}
	}
	
	public function indexAction() {
		if (null === ($q = $this->getRequest()->getParam('q'))) {
			$this->getResponse()
				->setHeader('X-Error-Msg', 'Missing required parameter `q`');
			throw new Zend_Controller_Exception('Missing required parameter `q`', 400);
		}
		$concepts = $this->model->getConcepts($q);
		$context = $this->_helper->contextSwitch()->getCurrentContext();
		if ($context === 'json' || $context === 'jsonp') {
			foreach ($concepts as $key => $val) {
				foreach ($val['docs'] as &$doc) unset($doc['xml']);
				$this->view->$key = $val;
			}
		} elseif ($context === 'xml') {
			$xpath = new DOMXPath($concepts);
			foreach ($xpath->query('/response/result/doc/str[@name="xml"]') as $node) {
				$node->parentNode->removeChild($node);
			}
			$this->view->response = $concepts;
		} else {
			$model = new OpenSKOS_Db_Table_Namespaces();
			$this->view->namespaces = $model->fetchPairs();
			$this->view->response = $concepts;
		}
	}

	public function getAction() {
		
		$concept = $this->_fetchConcept();
                $context = $this->_helper->contextSwitch()->getCurrentContext();
		if ($context == 'json' || $context == 'jsonp') {
			if (null !== $concept) {
				foreach ($concept as $key => $var) {
					if ($key == 'xml') continue;
					$this->view->$key = $var;
				}
			}
		} elseif ($this->_helper->contextSwitch()->getCurrentContext()==='xml') {
			$xpath = new DOMXPath($concept);
			foreach ($xpath->query('/doc/str[@name="xml"]') as $node) {
				$node->parentNode->removeChild($node);
			}
			$this->view->concept = $concept;
		} else {
			$this->view->concept = $concept;
		}
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
	
	/**
	 * @return Api_Models_Concept
	 */
	protected function _fetchConcept()
	{
		$id = $this->getRequest()->getParam('id');
		if (null === $id) {
			throw new Zend_Controller_Exception('No id `'.$id.'` provided', 400);
		}
                
                /*
                 * this is for clients that need special routes like "http://data.beeldenegluid.nl/gtaa/123456"
                 * with this we can create a route in the config ini like this:
                 * 
                 * resources.router.routes.route_id.type = "Zend_Controller_Router_Route_Regex"
                 * resources.router.routes.route_id.route = "gtaa\/(\d+)"
                 * resources.router.routes.route_id.defaults.module = "api"
                 * resources.router.routes.route_id.defaults.controller = "concept"
                 * resources.router.routes.route_id.defaults.action = "get"
                 * resources.router.routes.route_id.defaults.id_prefix = "http://data.beeldengeluid.nl/gtaa/"
                 * resources.router.routes.route_id.defaults.format = "html"
                 * resources.router.routes.route_id.map.1 = "id"
                 * resources.router.routes.route_id.reverse = "gtaa/%d"
                 */
                
                
                $id_prefix = $this->getRequest()->getParam('id_prefix');
                if (null!==$id_prefix && !OpenSKOS_Solr::isValidUuid($id)) {
                    $id_prefix  = str_replace('%tenant%', $this->getRequest()->getParam('tenant'), $id_prefix );
                    $id = $id_prefix . $id;
                }
		
        // Tries to find any not deleted concept.
		$concept = $this->model->getConcept($id);
		
		// If not deleted concept was not found - tries to find deleted one.
		if (null === $concept) {
			$concept = $this->model->getConcept($id, array(), true);
		}
		
		if (null === $concept) {
			throw new Zend_Controller_Exception('Concept `'.$id.'` not found', 404);
		}
		if ($concept->isDeleted()) {
			throw new Zend_Controller_Exception('Concept `'.$id.'` is deleted since '.$concept['timestamp'], 410);
		}
		return $concept;
	}

}

