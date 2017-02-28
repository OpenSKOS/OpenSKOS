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
    $this->viewpath = "relation/";
  }

  /*
   * Will be implemented in future versions when a relation triple becomes a stand-alone resource.
   * 
   */

  public function indexAction() {
    $this->_501('INDEX for relation triples as stand-alone resources');
  }

  /*
   * Will be implemented in future versions when a relation triple becomes a stand-alone resource.
   * 
   */

  public function getAction() {
    $this->_501('GET for a relation triple as a stand-alone resource');
  }

  /**
   *
   * @apiVersion 1.0.0
   * @apiDescription Create a new OpenSKOS relation instance (triple).
   *
   * @apiExample {String} Example request
   * Content-Type: application/json
   * Accept: application/json
   * concept=http://hdl.handle.net/11148/CCR_C-2731_5853a464-7c2d-53f9-d3cf-2f75a4dc4870&type=http://www.w3.org/2004/02/skos/core#narrower&related=http://hdl.handle.net/11148/CCR_C-2733_776d569f-94e2-7ffe-c679-d8b9bd8a4f12&key=xxx
   *
   * @api {post} /api/relation Create a relation triple
   * @apiName Create Relation instance (a triple)
   * @apiGroup Relation
   *
   * @apiParam {String} tenant The institute code for your institute in the OpenSKOS portal
   * @apiParam {String} key A valid API key
   * @apiSuccess {String} StatusCode 200 OK.
   * @apiSuccessExample {String} Success-Response:
   *   HTTP/1.1 200 OK
   * Relations added
   * 
   * @apiError MissingKey X-Error-Msg: No user key specified
   * @apiErrorExample MissingKey:
   *   HTTP/1.1 412 Precondition Failed
   *   No user key specified
   * 
   * @apiError MissingTenant X-Error-Msg:  No tenant specified
   * @apiErrorExample MissingTenant:
   *   HTTP/1.1 412 Precondition Failed
   *   No tenant specified
   * 
   * @apiError Not found X-Error-Msg: The concept referred by the uri &lt;uri&gt; does not exist. 
   * @apiErrorExample NotFound
   *   HTTP/1.1 404 NotFound
   *   The concept referred by the uri &lt;uri&gt; does not exist.
   * 
   * @apiError Not found X-Error-Msg: The relation type &lt;uir&gt;  is neither a skos concept-concept relation nor a user-defined relation type. 
   * @apiErrorExample NotFound
   * HTTP/1.1 404 NotFound
   * The relation type &lt;uir&gt;  is neither a skos concept-concept relation type nor a user-defined relation type. 
   * 
   * @apiError TransitiveLink X-Error-Msg: The triple creates transitive link of the source to itself, possibly via inverse relation.
   * @apiErrorExample TransitiveLink
   *   HTTP/1.1 400 Bad request
   *   The triple creates transitive link of the source to itself, possibly via inverse relation.
   * 
   */
  public function postAction() {
    $request = $this->getPsrRequest();
    $api = $this->getDI()->make('\OpenSkos2\Api\Concept');
    $response = $api->addRelationTriple($request);
    $this->emitResponse($response);
  }

  public function putAction() {
    $this->_501('PUT for a relation triple as a stand-alone resource');
  }

  /**
   *
   * @apiVersion 1.0.0
   * @apiDescription Delete a relation instance (triple).
   *
   * @apiExample {String} Example request
   * Content-Type: application/json
   * Accept: application/json
   * concept=http://hdl.handle.net/11148/CCR_C-2731_5853a464-7c2d-53f9-d3cf-2f75a4dc4870&type=http://www.w3.org/2004/02/skos/core#narrower&related=http://hdl.handle.net/11148/CCR_C-2733_776d569f-94e2-7ffe-c679-d8b9bd8a4f12&key=xxx
   *
   * @api {delete} /api/relation Delete a relation triple
   * @apiName Delete Relation instance (a triple)
   * @apiGroup Relation
   *
   * @apiParam {String} tenant The institute code for your institute in the OpenSKOS portal
   * @apiParam {String} key A valid API key
   * @apiSuccess {String} StatusCode 200 OK.
   * @apiSuccessExample {String} Success-Response:
   *   HTTP/1.1 200 OK
   * Relation deleted
   * 
   * @apiError MissingKey X-Error-Msg: No user key specified
   * @apiErrorExample MissingKey:
   *   HTTP/1.1 412 Precondition Failed
   *   No user key specified
   * 
   * @apiError MissingTenant X-Error-Msg:  No tenant specified
   * @apiErrorExample MissingTenant:
   *   HTTP/1.1 412 Precondition Failed
   *   No tenant specified
   * 
   * @apiError Not found X-Error-Msg: The concept referred by the uri &lt;uri&gt; does not exist. 
   * @apiErrorExample NotFound
   *   HTTP/1.1 404 NotFound
   *   The concept referred by the uri &lt;uri&gt; does not exist.
   * 
   * @apiError Not found X-Error-Msg: The relation type &lt;uir&gt;  is neither a skos concept-concept relation nor a user-defined relation type. 
   * @apiErrorExample NotFound
   * HTTP/1.1 404 NotFound
   * The relation type &lt;uir&gt;  is neither a skos concept-concept relation type nor a user-defined relation type. 
   * 
   *
   */
  public function deleteAction() {
    $request = $this->getPsrRequest();
    $api = $this->getDI()->make('\OpenSkos2\Api\Concept');
    $response = $api->deleteRelationTriple($request);
    $this->emitResponse($response);
  }
}
