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

// Meertens: 
// -- we have implemented autocomplete for html, whereas for Picturae it is blocked on init phase, 
// see 24837 issue
// -- Meertens code is refactored by introducing "handleContext" and "dispatchRequest" methods.
// -- there was no code changes of Pcitura since march 2016, only documentation and formatting 11/11/2016

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
      $request = $this->getRequest();
      if (null === ($q = $request->getParam('q'))) {
            $this->getResponse()
                ->setHeader('X-Error-Msg', 'Missing required parameter `q`');
            throw new Zend_Controller_Exception('Missing required parameter `q`', 400);
        }
        
        return $this->dispatchRequest($q, 'index');

    }

    /**
     * @apiVersion 1.0.0
     * @apiDescription  Autocomplete on labels of concepts matching the term. The autocomplete API is a simplified version of the Find concepts API.
     * You can use the autocomplete API in your projects, for example with as Javascript based autocompete field.
     *
     * Get all lexical labels in JSON format, with a word in one of the lexical labels starting with "dood":
     *
     * <a href='/api/autocomplete/dood' target='_blank'>/api/autocomplete/dood</a>
     *
     * This method returns all labels, including hiddenLabels and altLabels. This means it is possible that the service returns labels that do not match your pattern.
     *
     * Get only lexical labels with languagecode "nl" in JSON format, with a word in one of the lexical labels starting with "dood":
     *
     * <a href='/api/autocomplete/dood?lang=nl' target='_blank'>/api/autocomplete/dood?lang=nl</a>
     *
     * Get all lexical labels in JSON format, with a word in one in the prefLabels starting with "dood":
     *
     * <a href='/api/autocomplete/dood?searchLabel=prefLabel' target='_blank'>/api/autocomplete/dood?searchLabel=prefLabel</a>
     *
     * Get only prefLabels in JSON format, with a word in one in the labels starting with "dood":
     *
     * <a href='/api/autocomplete/dood?returnLabel=prefLabel' target='_blank'>/api/autocomplete/dood?returnLabel=prefLabel</a>
     *
     * Once the user selects a label from the autocomplete list, you have to lookup the matching Concept.
     * You can do this by querying the find API with the selected label, for example if the user selects the label "Dantons Dood":
     *
     * <a href='/api/find-concepts?q=prefLabel:"Dantons Dood"&fl=uri' target='_blank'>/api/find-concepts?q=prefLabel:"Dantons Dood"&fl=uri</a>
     *
     * Please note: in the second call to the find API, it's possible the API returns multiple concepts. You should implement methods to handle this!
     *
     * The following requests are possible:
     *
     * Must have a term in the path from the request:
     * <a href='/api/autocomplete/something' target='_blank'>/api/autocomplete/something</a>
     *
     * Can use parameters searchLabel, returnLabel and lang
     *
     * @api {get} /api/autocomplete Autocomplete
     * @apiName Autocomplete
     * @apiGroup FindConcept
     * @apiParam {String} something Term to search for
     * @apiParam {String} searchLabel Term label to search in
     * @apiParam {String} returnLabel Term label to return
     * @apiParam {String} lang Language to use for the searching
     * @apiSuccess {json} Body
     * @apiSuccessExample {String} Success-Response
     *   HTTP/1.1 200 Ok
     *   [
     *     'something'
     *     'somethingelse'
     *   ]
     *
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
