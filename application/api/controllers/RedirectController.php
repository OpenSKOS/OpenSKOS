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

class Api_RedirectController extends Zend_Controller_Action
{
	public function indexAction()
	{
		$this->model = Api_Models_Concepts::factory()->setQueryParams(
			$this->getRequest()->getParams()
		);
		$this->_helper->contextSwitch()
			->initContext($this->getRequest()->getParam('format', 'rdf'));
		
		$this->getHelper('layout')->disableLayout();
		
		$id = $this->getRequest()->getParam('id');
		if (null === $id) {
			throw new Zend_Controller_Exception('No id `'.$id.'` provided', 400);
		}
		
		$concept = $this->model->getConcept($id);
		if (null === $concept) {
			throw new Zend_Controller_Exception('Concept `'.$id.'` not found', 404);
		}
		
		$router = Zend_Controller_Front::getInstance()->getRouter();
		$uri = $router->assemble(array(
			'id' => $concept['uuid'],
			'module' => 'api',
			'controller' => 'concept',
		), 'rest', true);
		switch ($this->getRequest()->getParam('format')) {
			case 'json':
			case 'jsonp':
			case 'html':
				$uri .= '.' . $this->getRequest()->getParam('format');
				break;
		}
		$this->_helper->redirector->setGotoUrl($uri);
		$this->_helper->redirector->redirectAndExit();
	}

	public function getAction()
	{
		$this->indexAction();
	}
}