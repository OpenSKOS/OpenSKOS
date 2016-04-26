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

require_once 'AbstractController.php';

class API_UserrelationController extends AbstractController {
   
    public function init()
    {
       parent::init();
       $this->fullNameResourceClass = 'OpenSkos2\Api\UserRelation';
      
    }

    
     public function indexAction()
    {
       parent::indexAction();
    }
   
    public function getAction()
    {
        $listPairs = $this->getParam('members');
        if (!isset($listPairs) || $listPairs !== 'true') {
            $response = parent::getAction();
        } else {
            $this->_helper->viewRenderer->setNoRender(true);
            $request = $this->getPsrRequest();
            $api = $this->getDI()->make($this->fullNameResourceClass);
            $response = $api->findAllPairsForUserRelationType($request);
            $this->emitResponse($response);
        }
    }
    
    public function postAction()
    {
       parent::postAction();
    }
    
    public function putAction()
    {
        parent::putAction();
    }
    
    public function deleteAction()
    {
        parent::deleteAction();
    }
   
    /*
     public function indexAction() {
        $format = $this->getParam('format');
        if ('json' !== $format) {
            throw new Exception('Resource listing is implemented only in format=json', 404);
        }

        $request = $this->getPsrRequest();
        $api = $this->getDI()->make($this->fullNameResourceClass);
        $result = $api->fetchUriName($request);
        $this->_helper->contextSwitch()->setAutoJsonSerialization(false);
        $this->getResponse()->setBody(json_encode($result, JSON_UNESCAPED_SLASHES));
    }

    public function getAction()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $uri = $this->getUri();
        if (null === ($relationType = $this->getRequest()->getParam('q'))) {
            $this->getResponse()
                    ->setHeader('X-Error-Msg', 'Missing required parameter `q`');
            throw new Zend_Controller_Exception('Missing required parameter `q`', 400);
        }
        
        $apiRelations =$this->getDI()->make('\OpenSkos2\Api\UserRelation');
        $request = $this->getPsrRequest();
        $response = $apiRelations->findUserRelatedConcepts($request, $uri);
        $this->emitResponse($response);
    }
    
    public function postAction()
    {
        $request = $this->getPsrRequest();
        $relation = $this->getDI()->get('\OpenSkos2\Api\UserRelation');
        $response = $relation->create($request);
        $this->emitResponse($response);
    }

    public function putAction()
    {
         $request = $this->getPsrRequest();
        $relation = $this->getDI()->get('\OpenSkos2\Api\UserRelation');
        $response = $relation->update($request);
        $this->emitResponse($response);
    }
   
    public function deleteAction()
    {
        $request = $this->getPsrRequest();
        $relation = $this->getDI()->get('\OpenSkos2\Api\UserRelation');
        $response = $relation->deleteResourceObject($request);
        $this->emitResponse($response);
    }
    */
    
}
