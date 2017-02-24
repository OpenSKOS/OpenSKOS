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

/* 
 * Relation is a resource as for instance, a concept or a chema. User-defined relation definitions are stored in triple store as any other resource.
 * There is a difference between a relation as a definition (works for used-defined relations) and relation as a triple. This is reflectied
 * in the naming of methods, e.g. deleteRelation means deleting the relation definition (if no correspondingly related concepts are detected)
 * and deleteRelationTriple amounts to updating corresponding related concepts.
 * 
 */

class API_RelationController extends AbstractController {
    
   
    public function init() {
        parent::init();
        $this->fullNameResourceClass = 'OpenSkos2\Api\Relation';
        $this ->viewpath="relation/";
    }

      /**
     *
     * @apiVersion 1.0.0
     * @apiDescription Get SKOS Concept Schemata
    
     * Get a detailed list of SKOS concept schemata
     *
     * @api {get} /api/conceptscheme Get SKOS concept scheme
     * @apiName GetConceptSchemata
     * @apiGroup ConceptScheme
     *
     * @apiParam {String=empty, "rdf","html","json","jsonp"}  format If set to jsonp, the request must contain a non-empty parameter "callback" as well
     * @apiParam {String} callback If format set to jsonp, must be non-empty 
     * 
     * @apiSuccess (200) OK
     * @apiSuccessExample {String} Success-Response
     *   HTTP/1.1 200 OK
     * <?xml version="1.0"?>
     * <rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" 
     *  xmlns:dc="http://purl.org/dc/elements/1.1/" 
     *  xmlns:dcterms="http://purl.org/dc/terms/" 
     *  xmlns:skos="http://www.w3.org/2004/02/skos/core#"
     *  xmlns:openskos="http://openskos.org/xmlns#" 
     *  openskos:numFound="1" openskos:rows="5000" openskos:start="1">
     * <rdf:Description rdf:about="http://mertens/knaw/dataset_5980699b-2c9a-4717-ac30-aed13743cc84/conceptscheme_fed05e8d-f586-45b5-934a-7e8fccb61871">
    *  <rdf:type rdf:resource="http://www.w3.org/2004/02/skos/core#ConceptScheme"/>
    *    <dcterms:dateSubmitted rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2017-02-21T17:52:54+00:00</dcterms:dateSubmitted>
    *     <dcterms:creator rdf:resource="http://localhost/clavas/public/api/users/26272b05-6833-4ace-8e36-5b650fcefcdb"/>
    *     <openskos:set rdf:resource="http://mertens/knaw/dataset_5980699b-2c9a-4717-ac30-aed13743cc84"/>
    *     <dcterms:description xml:lang="nl">example1</dcterms:description>
    *     <dcterms:title xml:lang="nl">Schema 1</dcterms:title>
    *     <openskos:uuid>fed05e8d-f586-45b5-934a-7e8fccb61871</openskos:uuid>
    *     <openskos:tenant rdf:resource="http://mertens/knaw/formalorganization_10302a0e-7e4e-4dbb-bce0-59e2a21c8785"/>
    *   </rdf:Description>
    * </rdf:RDF>
    * 
    */

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
            if (isset($conceptUri)) { 
               // outputs all concepts-"targets" such that (conceptUri, relation, "target") holds if "isTarget=false" (default)
                // outputs all concepts-"sources" such that ("source", relation, conceptUri) holds if "isTarget=true" 
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

   
       /**
     *
     * @apiVersion 1.0.0
     * @apiDescription Create a OpenSKOS relation type  (user-defined relation type) 
    
     * Create a new OpenSKOS relation type based on the post data.
     * the attribute rdf:about in the rdf:description element is abligatory. It is of the form <namespace_uri>#<title>.
     * The relation type's title provided in the requests' body (xml) has an obligatory attribute "language".
     * The title must be unique per language and single per language.
     *
     @apiExample {String} Example request
     * <?xml version="1.0" encoding="UTF-8"?>
     * <rdf:RDF xmlns:rdf = "http://www.w3.org/1999/02/22-rdf-syntax-ns#"
     *          xmlns:openskos = "http://openskos.org/xmlns#"
     * xmlns:dcterms = "http://purl.org/dc/terms/">
     * <rdf:Description rdf:about="http://menzo.org/xmlns#slower">
     *        <dcterms:title xml:lang="nl">slower</dcterms:title>
     * </rdf:Description>
     * </rdf:RDF>
     *
     * @api {post} /api/conceptscheme Create SKOS concept scheme
     * @apiName CreateConceptScheme
     * @apiGroup ConceptScheme
     *
     * @apiParam {String} tenant The institute code for your institute in the OpenSKOS portal
     * @apiParam {String} key A valid API key
     * @apiParam {String="true","false","1","0"} autoGenerateIdentifiers If set to true (any of "1", "true", "on" and "yes") the concept uri (rdf:about) will be automatically generated.
     *                                           If uri exists in the xml and autoGenerateIdentifiers is true - an error will be thrown.
     *                                           If set to false - the xml must contain uri (rdf:about).
     * @apiSuccess (201) {String} ConceptScheme uri
     * @apiSuccessExample {String} Success-Response
     *   HTTP/1.1 201 Created
     * <?xml version="1.0" encoding="utf-8" ?>
     * <rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
     *       xmlns:openskos="http://openskos.org/xmlns#"
     *       xmlns:dcterms="http://purl.org/dc/terms/">
     *  <rdf:Description rdf:about="http://mertens/knaw/dataset_5980699b-2c9a-4717-ac30-aed13743cc84/conceptscheme_fed05e8d-f586-45b5-934a-7e8fccb61871">
     *     <rdf:type rdf:resource="http://www.w3.org/2004/02/skos/core#ConceptScheme"/>
     *     <dcterms:dateSubmitted rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2017-02-21T17:52:54+00:00</dcterms:dateSubmitted>
     *     <dcterms:creator rdf:resource="http://localhost/clavas/public/api/users/26272b05-6833-4ace-8e36-5b650fcefcdb"/>
     *     <openskos:set rdf:resource="http://mertens/knaw/dataset_5980699b-2c9a-4717-ac30-aed13743cc84"/>
     *     <dcterms:description xml:lang="nl">example1</dcterms:description>
     *     <dcterms:title xml:lang="nl">Schema 1</dcterms:title>
     *     <openskos:uuid>fed05e8d-f586-45b5-934a-7e8fccb61871</openskos:uuid>
     *   </rdf:Description>
     * </rdf:RDF>
     * 
     * @apiError MissingKey {String} X-Error-Msg: No user key specified
     * @apiErrorExample MissingKey:
     *   HTTP/1.1 412 Precondition Failed
     *   No user key specified
     * 
     * @apiError MissingTenant {String} X-Error-Msg:  No tenant specified
     * @apiErrorExample MissingTenant:
     *   HTTP/1.1 412 Precondition Failed
     *   No tenant specified
     * 
     * @apiError ConceptSchemeExists {String} X-Error-Msg: The resource with <id> already exists. Use PUT instead.
     * @apiErrorExample ConceptSchemeExists:
     *   HTTP/1.1 400 Bad request
     *   The resource with <id> already exists. Use PUT instead.
     * 
     * @apiError ValidationError {String} X-Error-Msg: The resource (of type http://www.w3.org/ns/org#Dataset) referred by  uri <sets's reference> is not found.
     * @apiErrorExample ValidationError: 
     *   HTTP/1.1 400 Bad request
     *   The resource (of type http://www.w3.org/ns/org#Dataset) referred by  uri <sets's reference> is not found.
     * 
     * @apiError ValidationError {String} X-Error-Msg: The resource with the property http://purl.org/dc/terms/title set to <dctermstitle> has been already registered.
     * @apiErrorExample ValidationError: 
     *   HTTP/1.1 400 Bad request
     *   The resource with the property http://purl.org/dc/terms/title set to <dctermstitle> has been already registered.
     *
     * @apiError ValidationError {String} X-Error-Msg: Title <dctermstitle> is given without language.
     * @apiErrorExample ValidationError: 
     *   HTTP/1.1 400 Bad request
     *   Title <dctermstitle>  is given without language.
     *
     * 
     */
     public function postAction() {
        $create = $this->getParam('create');
        if (isset($create) && $create === 'true') { // creating a new user-defined relation
            parent::postAction();
        } else { // adding a pair of related concepts (amounts to updating concepts)
            $request = $this->getPsrRequest();
            $api = $this->getDI()->make('\OpenSkos2\Api\Concept');
            $response = $api->addRelationTriple($request);
            $this->emitResponse($response);
        }

    }

    public function putAction()
    {
        parent::putAction();
    }
    
    public function deleteAction() {

        $remove = $this->getParam('removeType');
        if (isset($remove) && $remove === 'true') { // deleting a relation type
            parent::deleteAction();
        } else { // deleting a pair of related concepts
            $request = $this->getPsrRequest();
            $api = $this->getDI()->make('\OpenSkos2\Api\Concept');
            $response = $api->deleteRelationTriple($request);
            $this->emitResponse($response);
        }

    }

}
