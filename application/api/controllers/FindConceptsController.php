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
class Api_FindConceptsController extends OpenSKOS_Rest_Controller {

    /**
     *
     * @var Api_Models_Concepts
     */
    protected $model;

    public function init()
    {
        parent::init();
        $this->model = Api_Models_Concepts::factory()->setQueryParams(
                $this->getRequest()->getParams()
        );
        $this->_helper->contextSwitch()
                ->initContext($this->getRequestedFormat());

        if ('html' == $this->_helper->contextSwitch()->getCurrentContext()) {
            //enable layout:
            $this->getHelper('layout')->enableLayout();
        }
    }

    /**
     * Handle the following API Requests
     *
     * /api/find-concepts?q=Kim%20Holland
     * /api/find-concepts?q=K&start=10
     * /api/find-concepts?q=K&format=json
     * /api/find-concepts?format=json&q=inScheme:"http://data.beeldengeluid.nl/gtaa/GeografischeNamen" AND LexicalLabelsText:s*
     * /api/find-concepts?format=json&id=http://data.beeldengeluid.nl/gtaa/27140
     * /api/find-concepts?id=http://data.beeldengeluid.nl/gtaa/215866
     * /api/find-concepts?q=status:approved possible status (candidate|approved|redirected|not_compliant|rejected|obsolete|deleted)
     * /api/find-concepts?q=altLabel:kruisigingen 
     * /api/find-concepts?q=prefLabelText@nl:doodstraf
     * /api/find-concepts?q=altLabelText:kr* 
     * /api/find-concepts?q=notation:[* TO *]
     */
    public function indexAction()
    {
        if (null === ($q = $this->getRequest()->getParam('q'))) {
            $this->getResponse()
                    ->setHeader('X-Error-Msg', 'Missing required parameter `q`');
            throw new Zend_Controller_Exception('Missing required parameter `q`', 400);
        }
        
        $this->getHelper('layout')->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        
        $manager =  $this->getDI()->get('OpenSkos2\ConceptManager');
        $request = Zend\Diactoros\ServerRequestFactory::fromGlobals();
        $concept = new \OpenSkos2\Api\Concept($manager, $request);
        
        $context = $this->_helper->contextSwitch()->getCurrentContext();
        $response = $concept->findConcepts($context);
        (new \Zend\Diactoros\Response\SapiEmitter())->emit($response);
        exit; // find better way to prevent output from zf1
    }
    
    /**
     * Return an concept by the following requests
     * 
     * /api/concept/1b345c95-7256-4bb2-86f6-7c9949bd37ac.rdf
     * /api/concept/1b345c95-7256-4bb2-86f6-7c9949bd37ac.html
     * /api/concept/1b345c95-7256-4bb2-86f6-7c9949bd37ac.json
     */
    public function getAction()
    {
        $this->getHelper('layout')->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        
        $id = $this->getRequest()->getParam('id');
        if (null === $id) {
            throw new Zend_Controller_Exception('No id `' . $id . '` provided', 400);
        }
        
        /* @var $manager \OpenSkos2\ConceptManager */
        $manager =  $this->getDI()->get('OpenSkos2\ConceptManager');

        $request = Zend\Diactoros\ServerRequestFactory::fromGlobals();
        $concept = new \OpenSkos2\Api\Concept($manager, $request);        
        $context = $this->_helper->contextSwitch()->getCurrentContext();
        
        //var_dump($context); exit;
        $response = $concept->getConcept($id, $context);
        (new \Zend\Diactoros\Response\SapiEmitter())->emit($response);
        exit;        
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

    /**
     * @return Api_Models_Concept
     */
    protected function _fetchConcept()
    {
        $id = $this->getRequest()->getParam('id');
        if (null === $id) {
            throw new Zend_Controller_Exception('No id `' . $id . '` provided', 400);
        }

        /*
         * this is for clients that need special routes like "http://data.beeldenegluid.nl/gtaa/123456"
         * with this we can create a route in the config ini like this:
         *
         * resources.router.routes.route_id.type = "Zend_Controller_Router_Route_Regex"
         * resources.router.routes.route_id.route = "gtaa\/(\d+)"
         * resources.router.routes.route_id.defaults.module = "api"
         * resources.router.routes.route_id.defaults.controller = "concept"
         * resources.router.routes.route_id.defaults.action = "get"
         * resources.router.routes.route_id.defaults.id_prefix = "http://data.beeldengeluid.nl/gtaa/"
         * resources.router.routes.route_id.defaults.format = "html"
         * resources.router.routes.route_id.map.1 = "id"
         * resources.router.routes.route_id.reverse = "gtaa/%d"
         */


        $id_prefix = $this->getRequest()->getParam('id_prefix');
        if (null !== $id_prefix && !OpenSKOS_Solr::isValidUuid($id)) {
            $id_prefix = str_replace('%tenant%', $this->getRequest()->getParam('tenant'), $id_prefix);
            $id = $id_prefix . $id;
        }

        // Tries to find any not deleted concept.
        $concept = $this->model->getConcept($id);

        // If not deleted concept was not found - tries to find deleted one.
        if (null === $concept) {
            $concept = $this->model->getConcept($id, array(), true);
        }

        if (null === $concept) {
            throw new Zend_Controller_Exception('Concept `' . $id . '` not found', 404);
        }
        if ($concept->isDeleted()) {
            throw new Zend_Controller_Exception('Concept `' . $id . '` is deleted since ' . $concept['timestamp'], 410);
        }
        return $concept;
    }

    protected function shouldIncludeDeleted($q)
    {
        // Ultimate reliability
        return (strripos($q, 'status:deleted') !== false) && (!strripos($q, '-status:deleted') !== false);
    }

}
