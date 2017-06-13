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
class Api_InstitutionsController extends OpenSKOS_Rest_Controller
{
    public function init()
    {
        parent::init();
        $this->_helper->contextSwitch()
            ->initContext($this->getRequest()->getParam('format', 'rdf'));
        if ('html' == $this->_helper->contextSwitch()->getCurrentContext()) {
            //enable layout:
            $this->getHelper('layout')->enableLayout();
        }
    }
    /**
     * @apiVersion 1.0.0
     * @apiDescription Fetch all Institutions in this repository
     *
     * in RDF: <a href='/api/institutions' target='_blank'>/api/institutions</a>
     *
     * in JSON: <a href='/api/institutions?format=json' target='_blank'>/api/institutions?format=json</a>
     *
     * in JSONP: <a href='/api/institutions?format=jsonp&callback=myCallback_1234' target='_blank'>/api/institutions?format=jsonp&callback=myCallback_1234</a>
     *
     * in HTML: <a href='/api/institutions?format=html' target='_blank'>/api/institutions?format=html</a>
     *
     * @api {get} /api/institutions Get institutions details
     * @apiName GetInstitutions
     * @apiGroup Institutions
     * @apiSuccess (200) {String} XML
     * @apiSuccessExample {String} Success-Response
     *   HTTP/1.1 200 Ok
     *   &lt;rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:v="http://www.w3.org/2006/vcard/ns#">
     *       &lt;v:Vcard rdf:about="http://production.openskos.zend.picturae.pro/api/institutions/beng" v:url="http://www.beeldengeluid.nl">
     *         &lt;v:fn>Nederlands Instituut voor Beeld en Geluid&lt;/v:fn>
     *         &lt;rdf:Description>
     *           &lt;v:organisation-name>Nederlands Instituut voor Beeld en Geluid&lt;/v:organisation-name>
     *           &lt;v:organisation-unit>Afdeling Metadatabeheer&lt;/v:organisation-unit>
     *         &lt;/rdf:Description>
     *         &lt;v:email rdf:about="mailto:thesaurus@beeldengeluid.nl"/>
     *         &lt;v:adr>
     *           &lt;v:street-address>Media Park, Sumatralaan 45&lt;/v:street-address>
     *           &lt;v:locality>Hilversum&lt;/v:locality>
     *           &lt;v:postal-code>1851RW&lt;/v:postal-code>
     *           &lt;v:country-name>Nederland&lt;/v:country-name>
     *         &lt;/v:adr>
     *       &lt;/v:Vcard>
     *   &lt;/rdf:RDF>
     */
    public function indexAction()
    {
        $model = new OpenSKOS_Db_Table_Tenants();
        $context = $this->_helper->contextSwitch()->getCurrentContext();
        if ($context == 'json' || $context == 'jsonp') {
            $this->view->assign('institutions', $model->fetchAll()->toArray());
        } else {
            $this->view->tenants = $model->fetchAll();
        }
    }
    /**
     * @apiVersion 1.0.0
     * @apiDescription Fetch a single Institute from this repository
     *
     * in RDF: <a href='/api/institutions/beg' target='_blank'>/api/institutions/beg</a>
     *
     * in JSON: <a href='/api/institutions/beg.json' target='_blank'>/api/institutions/beg.json</a>
     *
     * in JSONP: <a href='/api/institutions/beg.jsonp?callback=myCallback_1234' target='_blank'>/api/institutions/beg.jsonp?callback=myCallback_1234</a>
     *
     * in HTML: <a href='/api/institutions/beg.html' target='_blank'>/api/institutions/beg.html</a>
     *
     * @api {get} /api/institutions/{id} Get institution details
     * @apiName GetInstitution
     * @apiGroup Institutions
     * @apiParam id {String} Institution code
     * @apiSuccess (200) {String} XML
     * @apiSuccessExample {String} Success-Response
     *   HTTP/1.1 200 Ok
     *   &lt;rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:v="http://www.w3.org/2006/vcard/ns#">
     *       &lt;v:Vcard rdf:about="http://production.openskos.zend.picturae.pro/api/institutions/beng" v:url="http://www.beeldengeluid.nl">
     *         &lt;v:fn>Nederlands Instituut voor Beeld en Geluid&lt;/v:fn>
     *         &lt;rdf:Description>
     *           &lt;v:organisation-name>Nederlands Instituut voor Beeld en Geluid&lt;/v:organisation-name>
     *           &lt;v:organisation-unit>Afdeling Metadatabeheer&lt;/v:organisation-unit>
     *         &lt;/rdf:Description>
     *         &lt;v:email rdf:about="mailto:thesaurus@beeldengeluid.nl"/>
     *         &lt;v:adr>
     *           &lt;v:street-address>Media Park, Sumatralaan 45&lt;/v:street-address>
     *           &lt;v:locality>Hilversum&lt;/v:locality>
     *           &lt;v:postal-code>1851RW&lt;/v:postal-code>
     *           &lt;v:country-name>Nederland&lt;/v:country-name>
     *         &lt;/v:adr>
     *       &lt;/v:Vcard>
     *   &lt;/rdf:RDF>
     */
    public function getAction()
    {
        $model = new OpenSKOS_Db_Table_Tenants();
        $code = $this->getRequest()->getParam('id');
        $tenant = $model->find($code)->current();
        if (null===$tenant) {
            throw new Zend_Controller_Action_Exception('Insitution `'.$code.'` not found', 404);
        }
        $context = $this->_helper->contextSwitch()->getCurrentContext();
        if ($context == 'json' || $context == 'jsonp') {
            foreach ($tenant as $key => $val) {
                $this->view->assign($key, $val);
            }
            $this->view->assign('collections', $tenant->findDependentRowset('OpenSKOS_Db_Table_Collections')->toArray());
        } else {
            $this->view->assign('tenant', $tenant);
        }
    }
    public function postAction()
    {
        $this->_501('post');
    }
    public function putAction()
    {
        $this->_501('put');
    }
    public function deleteAction()
    {
        $this->_501('delete');
    }
}