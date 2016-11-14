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

class Api_CollectionsController extends OpenSKOS_Rest_Controller
{
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
     * @apiVersion 1.0.0
     * @apiDescription Fetch all Collections/Sets in this repository
     *
     * in RDF: /api/collections
     *
     * in JSON: /api/collections?format=json
     *
     * in JSONP: /api/collections?format=jsonp&callback=myCallback_1234
     *
     * in HTML: /api/collections?format=html
     *
     * @api {get} /api/collections Get collections details
     * @apiName GetCollections
     * @apiGroup Collections
     * @apiParam {String="true","false","1","0","yes","no","y","n"} allow_oai If present return either only collections that are oai harvestable or just collections that can not be harvested.
     * @apiSuccess (200) {String} XML
     * @apiSuccessExample {String} Success-Response
     *   HTTP/1.1 200 Ok
     *   &lt;?xml version="1.0"?>
     *   &lt;rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:owl="http://www.w3.org/2002/07/owl#" xmlns:dcterms="http://purl.org/dc/terms/">
     *     &lt;rdf:Description rdf:about="http://production.openskos.zend.picturae.pro/api/collections/beng:gtaa">
     *       &lt;rdf:type rdf:resource="http://www.w3.org/2002/07/owl#Ontology"/>
     *       &lt;dcterms:title>Gemeenschappelijke Thesaurus Audiovisuele Archieven&lt;/dcterms:title>
     *       &lt;dcterms:description>De GTAA wordt gebruikt voor de beschrijving van audiovisuele collecties.&lt;/dcterms:description>
     *       &lt;dcterms:licence rdf:about="http://opendatacommons.org/licenses/odbl/1.0/">Open Database License (ODbL) v1.0&lt;/dcterms:licence>
     *       &lt;owl:sameAs rdf:about="http://gtaa.beeldengeluid.nl"/>
     *       &lt;dcterms:creator rdf:about="http://production.openskos.zend.picturae.pro/api/institutions/beng">Nederlands Instituut voor Beeld en Geluid&lt;/dcterms:creator>
     *     &lt;/rdf:Description>
     *     &lt;rdf:Description rdf:about="http://production.openskos.zend.picturae.pro/api/collections/beng:nongtaa">
     *       &lt;rdf:type rdf:resource="http://www.w3.org/2002/07/owl#Ontology"/>
     *       &lt;dcterms:title>Beeld en Geluid Thesaurus assen voor intern gebruik&lt;/dcterms:title>
     *       &lt;dcterms:description>In gebruik voor iMMix vanuit GTAA: &#xD;
     *       Genre, GeografischeNamen, Namen, Persoonsnamen, Onderwerpen, OnderwerpenBenG&#xD;
     *       &#xD;
     *       In gebruik in iMMix, niet in GTAA:&#xD;
     *       Productie, Zendgemachtigde, Doelgroep, &#xD;
     *       Taal, Zender, Muziekstijl, Nationaliteit&#xD;
     *       &lt;/dcterms:description>
     *       &lt;dcterms:licence rdf:about="http://opendatacommons.org/licenses/odbl/1.0/">Open Database License (ODbL) v1.0&lt;/dcterms:licence>
     *       &lt;dcterms:creator rdf:about="http://production.openskos.zend.picturae.pro/api/institutions/beng">Nederlands Instituut voor Beeld en Geluid&lt;/dcterms:creator>
     *     &lt;/rdf:Description>
     *     &lt;rdf:Description rdf:about="http://production.openskos.zend.picturae.pro/api/collections/beng:expired">
     *       &lt;rdf:type rdf:resource="http://www.w3.org/2002/07/owl#Ontology"/>
     *       &lt;dcterms:title>Vervallen Assen&lt;/dcterms:title>
     *       &lt;dcterms:description>De vervallen assen worden nog geraadpleegd, maar niet meer onderhouden. &lt;/dcterms:description>
     *       &lt;dcterms:licence rdf:about="http://opendatacommons.org/licenses/odbl/1.0/">Open Database License (ODbL) v1.0&lt;/dcterms:licence>
     *       &lt;dcterms:creator rdf:about="http://production.openskos.zend.picturae.pro/api/institutions/beng">Nederlands Instituut voor Beeld en Geluid&lt;/dcterms:creator>
     *     &lt;/rdf:Description>
     *     &lt;rdf:Description rdf:about="http://production.openskos.zend.picturae.pro/api/collections/beng:nonmatch">
     *       &lt;rdf:type rdf:resource="http://www.w3.org/2002/07/owl#Ontology"/>
     *       &lt;dcterms:title>test collection for non matching terms immix&lt;/dcterms:title>
     *       &lt;dcterms:licence rdf:about="http://opendatacommons.org/licenses/odbl/1.0/">Open Database License (ODbL) v1.0&lt;/dcterms:licence>
     *       &lt;dcterms:creator rdf:about="http://production.openskos.zend.picturae.pro/api/institutions/beng">Nederlands Instituut voor Beeld en Geluid&lt;/dcterms:creator>
     *     &lt;/rdf:Description>
     *   &lt;/rdf:RDF>
     */
    public function indexAction()
    {
        $model = new OpenSKOS_Db_Table_Collections();
        $context = $this->_helper->contextSwitch()->getCurrentContext();
        $select = $model->select();
        if (null !== ($allow_oai = $this->getRequest()->getParam('allow_oai'))) {
            switch (strtolower($allow_oai)) {
                case '1':
                case 'yes':
                case 'y':
                case 'true':
                    $select->where('allow_oai=?', 'Y');
                    break;
                case '0':
                case 'no':
                case 'n':
                case 'false':
                    $select->where('allow_oai=?', 'N');
                    break;
            }
        }
        if ($context == 'json' || $context == 'jsonp') {
            $this->view->assign('collections', $model->fetchAll($select)->toArray());
        } else {
            $this->view->collections = $model->fetchAll($select);
        }
    }

    /**
     * @apiVersion 1.0.0
     * @apiDescription Fetch a Collection/Set from this repository
     *
     * in RDF: /api/collections
     *
     * in JSON: /api/collections/{id}?format=json
     *
     * in JSONP: /api/collections/{id}?format=jsonp&callback=myCallback_1234
     *
     * in HTML: /api/collections/{id}?format=html
     *
     * @api {get} /api/collections/{id} Get collection details
     * @apiName GetCollection
     * @apiGroup Collections
     * @apiParam id {String} Collection/set code
     * @apiSuccess (200) {String} XML
     * @apiSuccessExample {String} Success-Response
     *   HTTP/1.1 200 Ok
     *   &lt;?xml version="1.0"?>
     *   &lt;rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:owl="http://www.w3.org/2002/07/owl#" xmlns:dcterms="http://purl.org/dc/terms/">
     *     &lt;rdf:Description rdf:about="http://production.openskos.zend.picturae.pro/api/collections/beng:nongtaa">
     *       &lt;rdf:type rdf:resource="http://www.w3.org/2002/07/owl#Ontology"/>
     *       &lt;dcterms:title>Beeld en Geluid Thesaurus assen voor intern gebruik&lt;/dcterms:title>
     *       &lt;dcterms:description>In gebruik voor iMMix vanuit GTAA: &#xD;
     *       Genre, GeografischeNamen, Namen, Persoonsnamen, Onderwerpen, OnderwerpenBenG&#xD;
     *       &#xD;
     *       In gebruik in iMMix, niet in GTAA:&#xD;
     *       Productie, Zendgemachtigde, Doelgroep, &#xD;
     *       Taal, Zender, Muziekstijl, Nationaliteit&#xD;
     *       &lt;/dcterms:description>
     *       &lt;dcterms:licence rdf:about="http://opendatacommons.org/licenses/odbl/1.0/">Open Database License (ODbL) v1.0&lt;/dcterms:licence>
     *       &lt;dcterms:creator rdf:about="http://production.openskos.zend.picturae.pro/api/institutions/beng">Nederlands Instituut voor Beeld en Geluid&lt;/dcterms:creator>
     *     &lt;/rdf:Description>
     *   &lt;/rdf:RDF>
     */
    public function getAction()
    {
        $modelTenant = new OpenSKOS_Db_Table_Tenants();
        $id = $this->getRequest()->getParam('id');
        list($tenantCode, $collectionCode) = explode(':', $id);
        $tenant = $modelTenant->find($tenantCode)->current();
        if (null===$tenant) {
            throw new Zend_Controller_Action_Exception('Insitution `'.$tenantCode.'` not found', 404);
        }

        $modelCollections = new OpenSKOS_Db_Table_Collections();
        $collection = $tenant->findDependentRowset(
            'OpenSKOS_Db_Table_Collections',
            null,
            $modelCollections->select()->where('code=?', $collectionCode)
        )->current();
        if (null===$collection) {
            throw new Zend_Controller_Action_Exception('Collection `'.$id.'` not found', 404);
        }

        $context = $this->_helper->contextSwitch()->getCurrentContext();
        if ($context == 'json' || $context == 'jsonp') {
            foreach ($collection as $key => $val) {
                $this->view->assign($key, $val);
            }
        } else {
            $this->view->assign('tenant', $tenant);
            $this->view->assign('collection', $collection);
            $this->view->assign(
                'schemesInCollection',
                $this->getConceptSchemeManager()->getSchemesByCollectionUri($collection->uri)
            );
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
