<?php

use EasyRdf\RdfNamespace;
use OpenSkos2\Namespaces\VCard;

abstract class AbstractController extends OpenSKOS_Rest_Controller
{

    protected $apiResourceClass;
    protected $viewpath;

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

    public function indexAction()
    {
        $api = $this->getDI()->make($this->apiResourceClass);
        $params = $this->getNormalizedRequestParameters();
        if ($params['shortlist']) { // needed for meertens browser
            $result = $api->mapNameSearchID();
            $this->_helper->contextSwitch()->setAutoJsonSerialization(false);
            return $this->getResponse()->setBody(json_encode($result, JSON_UNESCAPED_SLASHES));
        } else {
            if ($params['context'] === 'html') {
                $index = $api->mapNameSearchID();
                $this->view->resource = $index;
                return $this->renderScript($this->viewpath . 'index.phtml');
            } else {
                $response = $api->getResourceListResponse($params);
                $this->emitResponse($response);
            }
        }
    }

    public function getAction()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $id = $this->getId();
        $apiResource = $this->getDI()->make($this->apiResourceClass);
        $context = $this->_helper->contextSwitch()->getCurrentContext();
        if ('html' === $context) {
            $this->view->resource = $apiResource->getResource($id);
            $this->view->resProperties = $this->preparePropertiesForHTML($this->view->resource);
            return $this->renderScript($this->viewpath . 'get.phtml');
        }
        $request = $this->getPsrRequest();
        $response = $apiResource->getResourceResponse($request, $id, $context);
        $this->emitResponse($response);
    }

    public function postAction()
    {
        $request = $this->getPsrRequest();
        $api = $this->getDI()->make($this->apiResourceClass);
        $response = $api->create($request);
        $this->emitResponse($response);
    }

    public function putAction()
    {
        $request = $this->getPsrRequest();
        $api = $this->getDI()->make($this->apiResourceClass);
        $response = $api->update($request);
        $this->emitResponse($response);
    }

    public function deleteAction()
    {
        $request = $this->getPsrRequest();
        $api = $this->getDI()->make($this->apiResourceClass);
        $response = $api->delete($request);
        $this->emitResponse($response);
    }

    /**
     * Get concept id
     *
     * @throws Zend_Controller_Exception
     * @return string|\OpenSkos2\Rdf\Uri
     */
    private function getId()
    {
        $id = $this->getRequest()->getParam('id');
        if (null === $id) {
            throw new Zend_Controller_Exception('No id `' . $id . '` provided', 400);
        }
        if (strpos($id, 'http://') !== false || strpos($id, 'https://') !== false) {
            return new OpenSkos2\Rdf\Uri($id);
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
        if (null !== $id_prefix) {
            $id_prefix = str_replace('%tenant%', $this->getRequest()->getParam('tenant'), $id_prefix);
            $id = new OpenSkos2\Rdf\Uri($id_prefix . $id);
        }
        return $id;
    }

    /*
     * Input: request parameters (which may or may not contan context, format, and have 1 and 0, "yes" and "no" for boolean values)
     * Output: a mapping parameter->value, where values are filled in as completely as possible and standatized (true/false for booleans) 
     */

    private function getNormalizedRequestParameters()
    {

        $retVal = $this->getRequest()->getParams();

        $retVal['context'] = $this->_helper->contextSwitch()->getCurrentContext();
        // somehow the context is not re-initialised correctly when 'format=html" is declared
        if ($retVal['context'] == null) {
            $retVal['context'] = $retVal['format'];
        }


        $allow_oai = $this->getRequest()->getParam('allow_oai');
        if (null !== $allow_oai) {
            switch (strtolower($allow_oai)) {
                case '1':
                case 'yes':
                case 'y':
                case 'true':
                    $retVal['allow_oai'] = 'true';
                    break;
                case '0':
                case 'no':
                case 'n':
                case 'false':
                    $retVal['allow_oai'] = 'false';
                    break;
            }
        } else {
            $retVal['allow_oai'] = null;
        }

        // setting shortlist parameter for meertens browser (may be usful for other frontends)
        $shortlist = $this->getRequest()->getParam('shortlist');
        if ($shortlist === null) {
            $retVal['shortlist'] = false;
        } else {
            if ($shortlist === 'true' || $shortlist === '1') {
                $retVal['shortlist'] = true;
            } else {
                $retVal['shortlist'] = false;
            }
        }

        return $retVal;
    }

    private function preparePropertiesForHTML($resource)
    {
        $props = $resource->getProperties();
        $retVal = [];
        $shortADR = RdfNamespace::shorten(VCard::ADR);
        $shortORG = RdfNamespace::shorten(VCard::ORG);
        foreach ($props as $propname => $vals) {
            $shortName = RdfNamespace::shorten($propname);
            if ($shortName !== $shortADR && $shortName !== $shortORG) {
                $shortHTMLName = $this->shortenForHTML($propname);
                $retVal[$shortHTMLName] = implode(', ', $vals);
            } else { // recursive elements of organisation
                if ($vals !== null && isset($vals) && is_array($vals))
                    if (count($vals) > 0) {
                        foreach ($vals[0]->getProperties() as $key => $val2) {
                            $shortName2 = $this->shortenForHTML($key);
                            $retVal[$shortName2] = implode(', ', $val2);
                        }
                    }
            }
        }
        return $retVal;
    }

    private function shortenForHTML($key)
    {
        $parts = RdfNamespace::splitUri($key, false);
        return $parts[1];
    }

}
