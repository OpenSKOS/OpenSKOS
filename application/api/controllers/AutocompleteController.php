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

use OpenSkos2\FieldsMaps;
use OpenSkos2\Api\Response\Detail\JsonpResponse;

class Api_AutocompleteController extends OpenSKOS_Rest_Controller
{
    public function init()
    {
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
     * Can use parameters searchLabel, returnLabel and lang
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
        
        return $this->dispatchRequest($q, 'index');
    }

    /**
     * Returns a json response of pref / alt labels
     * 
     * Must have a term in the path from the request:
     * /api/autocomplete/something
     * 
     * Can use parameters searchLabel, returnLabel and lang
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
        $q = $this->getRequest()->getParam('id');
        return $this->dispatchRequest($q, 'get');
    }
    
      private function dispatchRequest($term, $filename){
        $params = $this->handleContext();
        $request = $this->getRequest();
        $result = $this->getConceptManager()->autoComplete(
            $term,
            FieldsMaps::getNamesToProperties()[$request->getParam('searchLabel', 'prefLabel')],
            FieldsMaps::getNamesToProperties()[$request->getParam('returnLabel', 'prefLabel')],
            $request->getParam('lang')
        );
        
        return $this->output($result, $params['context'], $term, $params['callback'], $filename);
    }
    
  
    
    private function handleContext() {
        $retVal=[];
        $retVal['callback'] = null;
        $retVal['context'] = $this->_helper->contextSwitch()->getCurrentContext();
        $request = $this->getRequest();
        $format = $request->getParam('format');
        if ($retVal['context'] === null) { // try to reset it via $format
            if ($format !== null) {
                if ('json' !== $format && 'html' !== $format && 'jsonp'!== $format) {
                    throw new Exception('Autocomplete listing is implemented only in formats json, jsonp or html', 404);
                } else {
                    $retVal['context'] = $format; 
                }
            } else {
                $retVal['context'] = 'json'; //default for index
            }
        }
        if ($retVal['context'] === 'jsonp') {
            $retVal['callback'] =  $request->getParam('callback');
        } 
        return $retVal;
    }
    
    
    private function output($result, $context, $term, $callback, $filename) {
        if ($context === 'json') {
            $this->_helper->contextSwitch()->setAutoJsonSerialization(false);
            $this->getResponse()->setBody(
                    json_encode($result)
            );
        } else {
            if ($context === 'jsonp') {
                $response = JsonpResponse::produceJsonPResponse($result, $callback);
                $this->emitResponse($response);
                
            } else { //only "html" is left as a possible option
                $this->getHelper('layout')->enableLayout();
                $this->view->items = $result;
                $this->view->term = $term;
                return $this->renderScript('/autocomplete/'.$filename.'.phtml');
            }
        }
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
