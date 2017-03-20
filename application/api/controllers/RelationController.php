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

class API_RelationController extends OpenSKOS_Rest_Controller {

    public function indexAction()
    {
        $this->_501('GET');
    }

    public function getAction()
    {
        $this->_501('GET');
    }

    /**
     * @apiVersion 1.0.0
     * @apiDescription Add a relation to a SKOS Concept
     *
     * The relation will be added on both sides, so if you add
     * 2 concepts as narrower the narrower concepts will be updated with broader
     *
     * Post must be send with Content-Type application/x-www-form-urlencoded
     * or multipart/form-data
     *
     * Supported relations:<br>
     * <a href='http://www.w3.org/2004/02/skos/core#broader' target='_blank'>http://www.w3.org/2004/02/skos/core#broader</a><br>
     * <a href='http://www.w3.org/2004/02/skos/core#narrower' target='_blank'>http://www.w3.org/2004/02/skos/core#narrower</a><br>
     * <a href='http://www.w3.org/2004/02/skos/core#hasTopConcept' target='_blank'>http://www.w3.org/2004/02/skos/core#hasTopConcept</a><br>
     * <a href='http://www.w3.org/2004/02/skos/core#topConceptOf' target='_blank'>http://www.w3.org/2004/02/skos/core#topConceptOf</a><br>
     * <a href='http://www.w3.org/2004/02/skos/core#related' target='_blank'>http://www.w3.org/2004/02/skos/core#related</a><br>
     * <a href='http://www.w3.org/2004/02/skos/core#broadMatch' target='_blank'>http://www.w3.org/2004/02/skos/core#broadMatch</a><br>
     * <a href='http://www.w3.org/2004/02/skos/core#narrowMatch' target='_blank'>http://www.w3.org/2004/02/skos/core#narrowMatch</a><br>
     *
     * @api {post} /api/relation Add relation to SKOS concept
     * @apiName AddRelationConcept
     * @apiGroup Concept
     * @apiParam {String} concept The uri to the concept e.g http://openskos.org/1
     * @apiParam {String} type The uri of the relation e.g http://www.w3.org/2004/02/skos/core#narrower
     * @apiParam {Array}  related The uri's of the related concepts e.g http://openskos.org/123
     * @apiParam {String} key A valid API key
     * @apiSuccess (200) {String} Concept uri
     * @apiSuccessExample {String} Success-Response
     *   HTTP/1.1 200 Ok
     * @apiError MissingKey {String} No key specified
     * @apiErrorExample MissingKey
     *   HTTP/1.1 412 Precondition Failed
     *   No key specified
     */
    public function postAction()
    {
        $request = $this->getPsrRequest();
        /* @var $relation \OpenSkos2\Api\Relation */
        $relation = $this->getDI()->get('\OpenSkos2\Api\Relation');
        $response = $relation->addRelation($request);
        $this->emitResponse($response);
    }

    public function putAction()
    {
        $this->_501('PUT');
    }

    /**
     * @apiVersion 1.0.0
     * @apiDescription Delete a relation of a SKOS Concept
     * The relation will be deleted from both sides.
     * If narrower is deleted from the subject the respective broader will be deleted from the object.
     *
     * @api {delete} /api/relation Delete relation from SKOS concept
     * @apiName DeleteRelationConcept
     * @apiGroup Concept
     * @apiParam {String} concept The uri to the concept e.g http://openskos.org/1
     * @apiParam {String} type The uri of the relation e.g http://www.w3.org/2004/02/skos/core#narrower
     * @apiParam {Array}  related The uri's of the related concepts e.g http://openskos.org/123
     * @apiParam {String} key A valid API key
     * @apiSuccess (200) {String} Concept uri
     * @apiSuccessExample {String} Success-Response
     *   HTTP/1.1 200 Ok
     * @apiError MissingKey {String} No key specified
     * @apiErrorExample MissingKey
     *   HTTP/1.1 412 Precondition Failed
     *   No key specified
     */
    public function deleteAction()
    {
        $request = $this->getPsrRequest();
        /* @var $relation \OpenSkos2\Api\Relation */
        $relation = $this->getDI()->get('\OpenSkos2\Api\Relation');
        $response = $relation->deleteRelation($request);
        $this->emitResponse($response);
    }
}
