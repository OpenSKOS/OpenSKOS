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

require_once 'AbstractRelationController.php';

class API_SkosrelationController extends AbstractRelationController {
   
    public function init() {
        parent::init();
        $this->fullNameResourceClass = 'OpenSkos2\Api\SkosRelation';
    }

    public function indexAction()
    {
       parent::indexAction();
    }
   
    public function getAction()
    {
        parent::getAction();
    }

   
    
    public function postAction()
    {
       $request = $this->getPsrRequest();
        $relation = $this->getDI()->get('\OpenSkos2\Api\SkosRelation');
        $response = $relation->addSkosRelation($request);
        $this->emitResponse($response);
    }

    public function putAction()
    {
        $this->_501('PUT');
    }

  
    public function deleteAction()
    {
        $request = $this->getPsrRequest();
        $relation = $this->getDI()->get('\OpenSkos2\Api\SkosRelation');
        $response = $relation->deleteSkosRelation($request);
        $this->emitResponse($response);
    }
    
    
}
