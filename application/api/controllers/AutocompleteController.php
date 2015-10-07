<?php

/*
 * OpenSKOS
 *
 * LICENSE
 *
 * This source file is subject to the GPLv3 license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @category   OpenSKOS
 * @package    OpenSKOS
 * @copyright  Copyright (c) 2015 Picturae (http://www.picturae.com)
 * @author     Picturae
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 */

class Api_AutocompleteController extends OpenSKOS_Rest_Controller
{

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
    
    /**
     * Returns a json response of pref / alt labels
     * 
     * Must have a q query parameter in the request example:
     * /api/autocomplete?q=something
     * 
     * Returns
     * 
     * [
     *  'something'
     *  'somethingelse'
     * ]
     * 
     * @throws Zend_Controller_Exception
     */
    public function indexAction()
    {
        if (null === ($q = $this->getRequest()->getParam('q'))) {
            $this->getResponse()
                ->setHeader('X-Error-Msg', 'Missing required parameter `q`');
            throw new Zend_Controller_Exception('Missing required parameter `q`', 400);
        }
        
        $result = $this->getConceptManager()->autoComplete($q);
        
        $this->_helper->contextSwitch()->setAutoJsonSerialization(false);
        $this->getResponse()->setBody(
            json_encode($result)
        );
    }

    /**
     * Returns a json response of pref / alt labels
     * 
     * Must have a term in the path from the request:
     * /api/autocomplete/something
     * 
     * Returns
     * 
     * [
     *  'something'
     *  'somethingelse'
     * ]
     * 
     * @throws Zend_Controller_Exception
     */
    public function getAction()
    {
        $this->_helper->contextSwitch()->setAutoJsonSerialization(false);
        
        $q = $this->getRequest()->getParam('id');
        $result = $this->getConceptManager()->autoComplete($q);
        
        $this->getResponse()->setBody(
            json_encode($result)
        );
    }

    public function postAction()
    {
        $this->_501('POST');
    }

    public function putAction()
    {
        $this->_501('POST');
    }

    public function deleteAction()
    {
        $this->_501('DELETE');
    }
}
