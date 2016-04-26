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

class AbstractRelationController extends AbstractController {
   
     public function indexAction()
    {
       parent::indexAction();
    }
   
  
    
    public function getAction() {
        $listPairs = $this->getParam('members');
        if (!isset($listPairs) || $listPairs !== 'true') {
            $this->_helper->viewRenderer->setNoRender(true);
            $conceptUri = $this->getParam('conceptUri');
            if (!isset($conceptUri)) {
                $response = parent::getAction(); // simply gives the relation description
            } else { // gives all concepts related to the one with conceptUri
                $request = $this->getPsrRequest();
                $api = $this->getDI()->make($this->fullNameResourceClass);
                $response = $api->findRelatedConcepts($request, $conceptUri);
                $this->emitResponse($response);
            }
        } else {
            // gives all pairs of concepts in this relation, lists pairs
            $request = $this->getPsrRequest();
            $api = $this->getDI()->make($this->fullNameResourceClass);
            $response = $api->findAllPairsForRelation($request);
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
}
