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

class Api_FindConceptsController extends OpenSKOS_Rest_Controller {

    public function init()
    {
        parent::init();

        $this->_helper->contextSwitch()
                ->initContext($this->getRequestedFormat());

        if ('html' == $this->_helper->contextSwitch()->getCurrentContext()) {
            //enable layout:
            $this->getHelper('layout')->enableLayout();
        }
    }

    /**
     * The following requests are possible
     *
     * /api/find-concepts?q=doood
     * /api/find-concepts?q=do*
     * /api/find-concepts?q=prefLabel:dood
     * /api/find-concepts?q=do* status:approved
     * /api/find-concepts?q=prefLabel:do*&rows=0&format=json
     * /api/find-concepts?q=prefLabel@nl:doo
     * /api/find-concepts?q=prefLabel@nl:do*
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

        $manager = $this->getDI()->get('OpenSkos2\ConceptManager');

        $concept = new \OpenSkos2\Api\Concept($manager);

        $context = $this->_helper->contextSwitch()->getCurrentContext();
        $request = $this->getPsrRequest();
        $response = $concept->findConcepts($request, $context);
        $this->emitResponse($response);        
    }

    /**
     * Return an concept by the following requests
     *
     * /api/concept/1b345c95-7256-4bb2-86f6-7c9949bd37ac.rdf
     * /api/concept/1b345c95-7256-4bb2-86f6-7c9949bd37ac.html
     * /api/concept/1b345c95-7256-4bb2-86f6-7c9949bd37ac.json
     * /api/concept/82c2614c-3859-ed11-4e55-e993c06fd9fe.jsonp&callback=test
     */
    public function getAction()
    {
        $this->_helper->viewRenderer->setNoRender(true);

        $id = $this->getId();

        /* @var $manager \OpenSkos2\ConceptManager */
        $manager = $this->getDI()->get('OpenSkos2\ConceptManager');

        $apiConcept = new \OpenSkos2\Api\Concept($manager);
        $context = $this->_helper->contextSwitch()->getCurrentContext();

        // Exception for html use ZF 1 easier with linking in the view
        if ('html' === $context) {
            $this->view->concept = $apiConcept->getConcept($id);
            return $this->renderScript('concept/get.phtml');
        }

        $request = $this->getPsrRequest();
        $response = $apiConcept->getConceptResponse($request, $id, $context);
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

    /**
     * Get concept id
     *
     * @throws Zend_Controller_Exception
     * @return string
     */
    private function getId()
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

        return $id;
    }
}
