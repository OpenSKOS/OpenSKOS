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

class API_FindRelationsController extends OpenSKOS_Rest_Controller {
   
     public function init() {
        parent::init();
    }

    public function indexAction()
    {
        if (null === ($q = $this->getRequest()->getParam('q'))) {
            $this->getResponse()
                    ->setHeader('X-Error-Msg', 'Missing required parameter `q`');
            throw new Zend_Controller_Exception('Missing required parameter `q`', 400);
        }
        
        $relations =$this->getDI()->make('\OpenSkos2\Api\Relation');
        $request = $this->getPsrRequest();
        $response = $relations->findAllPairsForRelationType($request);
        $this->emitResponse($response);
    }
    
    public function getAction()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $uri = $this->getUri();
        if (null === ($relationType = $this->getRequest()->getParam('relationType'))) {
            $this->getResponse()
                    ->setHeader('X-Error-Msg', 'Missing required parameter `relationType`');
            throw new Zend_Controller_Exception('Missing required parameter `relationType`', 400);
        }
        
        $request = $this->getPsrRequest();
        $apiRelations =$this->getDI()->make('\OpenSkos2\Api\Relation');
        $response = $apiRelations->findRelatedConcepts($request, $uri);
        $this->emitResponse($response);
    }
   
    public function postAction()
    {
       $this->_501('POST');
    }

    public function putAction()
    {
        $this->_501('PUT');
    }

   
    public function deleteAction()
    {
       $this->_501('DELETE');
    }
    
     private function getUri() {
        $uri = $this->getRequest()->getParam('id');
        if (null === $uri) {
            throw new Zend_Controller_Exception('No uri as id value provided', 400);
        }

        if (strpos($uri, 'http://') !== false || strpos($uri, 'https://') !== false) {
            return new OpenSkos2\Rdf\Uri($uri);
        } else {
            throw new Zend_Controller_Exception('Uri must start with http:// or https://', 400);
        }
    }

}
