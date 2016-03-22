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
require_once 'FindInstitutionsController.php';

class Api_InstitutionController extends Api_FindInstitutionsController
{
    public function init()
    {
        $this->getHelper('layout')->disableLayout();
        $this->getHelper('viewRenderer')->setNoRender(true);
        parent::init();
    }
    
   
    public function getAction()
    {
       $this->_helper->viewRenderer->setNoRender(true);
       $api = $this->getDI()->make('OpenSkos2\Api\Tenant');
        
        // Exception for html use ZF 1 easier with linking in the view
        if ('html' === $this ->getParam('format')) {
            //$this->view->concept = $apiConcept->getConcept($id);
            //return $this->renderScript('concept/get.phtml');
            throw new Exception('HTML format is not implemented yet', 404);
        }
        
        $request = $this->getPsrRequest();
        $response = $api->findResourceById($request);
        $this->emitResponse($response);
    }
    
    public function postAction()
    {
        $request = $this->getPsrRequest();
        $api = $this->getDI()->make('\OpenSkos2\Api\Tenant');
        $response = $api->create($request);
        $this->emitResponse($response);
    }
    
    public function putAction()
    {
        $request = $this->getPsrRequest();
        $api = $this->getDI()->make('\OpenSkos2\Api\Tenant');
        $response = $api->update($request);
        $this->emitResponse($response);
    }
    
    public function deleteAction()
    {
        $request = $this->getPsrRequest();
        $api = $this->getDI()->make('\OpenSkos2\Api\Tenant');
        $response = $api->deleteResourceObject($request);
        $this->emitResponse($response);
    }
}
