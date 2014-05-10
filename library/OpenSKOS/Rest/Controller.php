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

abstract class OpenSKOS_Rest_Controller extends Zend_Rest_Controller
{
    public $contexts = array(
        'index' => array('json', 'jsonp', 'xml', 'rdf'),
        'get' => array('json', 'jsonp', 'xml', 'rdf', 'html'),
        'post' => array('json', 'jsonp', 'xml'),
        'put' => array('json', 'jsonp', 'xml'),
        'delete' => array('json', 'jsonp', 'xml'),
    );

    protected function _501($method)
    {
        $this->getResponse()
            ->setHeader('X-Error-Msg', $method.' not implemented');
        throw new Zend_Controller_Exception($method.' not implemented', 501);
    }

    public function init() 
    {
        //format as an extention hack:
        $id = $this->getRequest()->getParam('id');
        if (null!==$id) {
            if (preg_match('/\.(xml|rdf|html|json|jsonp)$/', $id, $match)) {
                $id = preg_replace('/\.(xml|rdf|html|json|jsonp)$/', '', $id);
                $format = $match[1];
                $this->getRequest()->setParam('format', $format);				
            }
            $this->getRequest()->setParam('id', $id);
        }
        
        $jsonpContext = new OpenSKOS_Controller_Action_Context_Jsonp(
            $this->_helper->contextSwitch()
        );

        $this->_helper->contextSwitch()
            ->addContext(
                'rdf',
                array(
                    'suffix' => 'rdf',
                    'headers' => array(
                        'Content-Type' => 'text/xml; charset=UTF-8'
                    )
                )
            )
            ->addContext(
                'html',
                array(
                    'suffix' => '',
                    'headers' => array(
                        'Content-Type' => 'text/html; charset=UTF-8'
                    )
                )
            )
            ->addContext(
                'jsonp',
                OpenSKOS_Controller_Action_Context_Jsonp::factory(
                    $this->_helper->contextSwitch()
                )
                ->getContextSettings()
            )
            ->initContext($this->getRequest()->getParam('format'));

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