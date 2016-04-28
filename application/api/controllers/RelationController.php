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

class API_RelationController extends AbstractController {
   
    public function init() {
        parent::init();
        $this->fullNameResourceClass = 'OpenSkos2\Api\Relation';
    }

    public function indexAction()
    {
       parent::indexAction();
    }
   
    public function getAction() {
        $this->_helper->viewRenderer->setNoRender(true);
        $listPairs = $this->getParam('members');
        if (isset($listPairs) && $listPairs === 'true') {
            // lists all pairs of concepts in this relation
            $request = $this->getPsrRequest();
            $api = $this->getDI()->make($this->fullNameResourceClass);
            $response = $api->findAllPairsForRelation($request);
            $this->emitResponse($response);
        } else {
            $conceptUri = $this->getParam('conceptUri');
            if (isset($conceptUri)) { // gives all concepts related to the one with conceptUri
                $request = $this->getPsrRequest();
                $api = $this->getDI()->make($this->fullNameResourceClass);
                $format = $this->getRequestedFormat();
                $response = $api->findRelatedConcepts($request, $conceptUri, $format);
                $this->emitResponse($response);
            } else { // simply gives the relation description.
                // not implemented for skos-relations, but only for user-defined relations
                $id = $this -> getParam('id');
                if (substr($id, 0, strlen('http://www.w3.org/2004/02/skos/core')) === 'http://www.w3.org/2004/02/skos/core') {
                    throw new Exception('There is no relation description for skos relations', 404);
                }
                $response = parent::getAction(); 
            }
        }
    }

   
    
     public function postAction() {
        $create = $this->getParam('create');
        if (isset($create) && $create === 'true') { // creating a new user-defined relation
            parent::postAction();
        } else { // adding a pair of related concepts
            $request = $this->getPsrRequest();
            $api = $this->getDI()->make($this->fullNameResourceClass);
            $response = $api->addRelation($request);
            $this->emitResponse($response);
        }
    }

    public function putAction()
    {
        parent::putAction();
    }
    
    public function deleteAction() {

        $remove = $this->getParam('removeDefinition');
        if (isset($remove) && $remove === 'true') { // deleting a user-defined relation
            parent::deleteAction();
        } else { // deleting a pair of related concepts
            $request = $this->getPsrRequest();
            $api = $this->getDI()->make($this->fullNameResourceClass);
            $response = $api->deleteRelation($request);
            $this->emitResponse($response);
        }
    }

}
