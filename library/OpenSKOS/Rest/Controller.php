<?php
abstract class OpenSKOS_Rest_Controller extends Zend_Rest_Controller
{
	public $contexts = array(
		'index' => array('json', 'xml', 'rdf'),
		'get' => array('json', 'xml', 'rdf', 'html'),
		'post' => array('json', 'xml'),
		'put' => array('json', 'xml'),
		'delete' => array('json', 'xml'),
	);
	
	protected function _501($method)
	{
		$this->getResponse()
			->setHeader('X-Error-Msg', $method.' not implemented');
		throw new Zend_Controller_Exception($method.' not implemented', 501);
	}
	
	public function init() {
		$this->_helper->contextSwitch()
			->addContext('rdf', array(
				'suffix' => 'rdf',
				'headers' => array(
					'Content-Type' => 'text/xml; charset=UTF-8'
				)
			)
		)->initContext($this->getRequest()->getParam('format'));
		
		foreach($this->getResponse()->getHeaders() as $header) {
			if ($header['name'] == 'Content-Type') {
				if (false === stripos($header['value'], 'utf-8')) {
					$this->getResponse()->setHeader($header['name'], $header['value'].'; charset=UTF-8', true);
				}
				break;
			}
		}
	}
	
}