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
   
    public function init() {
        parent::init();
        $this->_helper->contextSwitch()
                ->initContext($this->getRequestedFormat());

        if ('html' == $this->_helper->contextSwitch()->getCurrentContext()) {
            //enable layout:
            $this->getHelper('layout')->enableLayout();
        }
    }

    public function indexAction() {
        $relations = $this->getDI()->make('\OpenSkos2\Api\UserRelation');
        $q = $this->getRequest()->getParam('q');
        if ($q === null) {
            $response = $relations->listAllUserRelations();
        } else {
            $request = $this->getPsrRequest();
            $response = $relations->findAllPairsForUserRelationType($request);
        }

        $this->emitResponse($response);
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
        /* @var $relation \OpenSkos2\Api\SkosRelation */
        $relation = $this->getDI()->get('\OpenSkos2\Api\UserRelation');
        $response = $relation->create($request);
        $this->emitResponse($response);
    }

    public function putAction()
    {
         $request = $this->getPsrRequest();
        /* @var $relation \OpenSkos2\Api\SkosRelation */
        $relation = $this->getDI()->get('\OpenSkos2\Api\UserRelation');
        $response = $relation->update($request);
        $this->emitResponse($response);
    }

    /**
    * @apiVersion 1.0.0
    * @apiDescription Add a relation to a SKOS Concept
    * The relation will be deleted from both sides.
    * If narrower is deleted form the subject the respective broader will be deleted from the object.
    * 
    * Post must be send with Content-Type application/x-www-form-urlencoded
    * or multipart/form-data
    * 
    * @api {delete} /api/relation Add relation to SKOS concept
    * @apiName RelationConcept
    * @apiGroup Concept
    * @apiParam {String} concept The uri to the concept e.g http://openskos.org/1
    * @apiParam {String} type The uri of the relation e.g http://www.w3.org/2004/02/skos/core#narrower
    * @apiParam {Array}  related The uri's of the related concepts e.g http://openskos.org/123
    * @apiParam {String} key A valid API key
    * @apiSuccess (200) {String} Concept uri
    * @apiSuccessExample {String} Success-Response
    *   HTTP/1.1 200 Ok
    * @apiError MissingKey {String} No key specified
    * @apiErrorExample MissingKey:
    *   HTTP/1.1 412 Precondition Failed
    *   No key specified
    */
    public function deleteAction()
    {
        $request = $this->getPsrRequest();
        /* @var $relation \OpenSkos2\Api\SkosRelation */
        $relation = $this->getDI()->get('\OpenSkos2\Api\UserRelation');
        $response = $relation->deleteResourceObject($request);
        $this->emitResponse($response);
    }
    
    
}
