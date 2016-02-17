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

namespace OpenSkos2\Api;
use OpenSkos2\Api\Response\ResultSet\JsonResponse;
use OpenSkos2\Namespaces\Skos;

class Relation
{
    use \OpenSkos2\Api\Response\ApiResponseTrait;
    /**
     * @var \OpenSkos2\ConceptManager
     */
    protected $manager;

    /**
     * @param \OpenSkos2\ConceptManager $manager
     */
    public function __construct(\OpenSkos2\ConceptManager $manager)
    {
        $this->manager = $manager;
    }
    
    public function findAllPairsForRelationType($request) {
        //public function findAllPairsForType(ServerRequestInterface $request)
        $relType = $request->getQueryParams()['q'];
        try {
            $response = $this->manager->fetchAllRelations($relType);
            $intermediate = $this->createRelationTriples($response, $relType);
            $result = new \Zend\Diactoros\Response\JsonResponse($intermediate);
            return $result;
        } catch (Exception $exc) {
            if ($exc instanceof \OpenSkos2\Api\Exception\ApiException) {
                return $this->getErrorResponse($exc->getCode(), $exc->getMessage());
            } else {
                return $this->getErrorResponse(500, $exc->getMessage());
            }
        }
    }
    
    public function findRelatedConcepts($request, $uri) {
        // params:uri, relationType, conceptScheme
        $params = $request->getQueryParams();
        $relType = 'http://www.w3.org/2004/02/skos/core#' . $params['relationType'];
        if (!in_array($relType, Skos::getRelationsTypes())){
           return $this->getErrorResponse(501, 'Relation ' . $params['relationType'] . ' is not implemented.'); 
        }
        if (isset($params['inSchema'])) {
            $schema = $params['inSchema'];
        } else {
            $schema = null;
        }
        try {
            $concepts = $this->manager->fetchRelations($uri, $relType, $schema);
            //var_dump($concepts);
            $result = new ResourceResultSet($concepts, $concepts->count(), 0, 100000);
            $response = (new JsonResponse($result, []))->getResponse();
            return $response;
        } catch (Exception $exc) {
            if ($exc instanceof \OpenSkos2\Api\Exception\ApiException) {
                return $this->getErrorResponse($exc->getCode(), $exc->getMessage());
            } else {
                return $this->getErrorResponse(500, $exc->getMessage());
            }
        }
    }
    

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return Zend\Diactoros\Response
     */
    //Olha was here
    //public function addRelation(\Psr\Http\Message\ServerRequestInterface $request)
    public function addRelation($request)
    {
        try {
            $this->addConceptRelation($request);
        } catch (Exception\ApiException $exc) {
            return $this->getErrorResponse($exc->getCode(), $exc->getMessage());
        }

        $stream = new \Zend\Diactoros\Stream('php://memory', 'wb+');
        $stream->write('Relations added');
        $response = (new \Zend\Diactoros\Response())
                ->withBody($stream);
        return $response;
    }
    
    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return Zend\Diactoros\Response
     */
    public function deleteRelation(\Psr\Http\Message\ServerRequestInterface $request)
    {
        try {
            $this->deleteConceptRelation($request);
        } catch (Exception\ApiException $exc) {
            return $this->getErrorResponse($exc->getCode(), $exc->getMessage());
        }

        $stream = new \Zend\Diactoros\Stream('php://memory', 'wb+');
        $stream->write('Relation deleted');
        $response = (new \Zend\Diactoros\Response())
            ->withBody($stream);
        return $response;
    }
    
    /**
     * Add concept relation
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @throws Exception\ApiException
     */
    // Olha was here
    //protected function addConceptRelation(\Psr\Http\Message\ServerRequestInterface $request)
    protected function addConceptRelation($request)
    {
       /* 
        * Ola was here
        * $body = $request->getParsedBody();
        
        if (!isset($body['key'])) {
            throw new Exception\ApiException('Missing key', 400);
        }
        if (!isset($body['concept'])) {
            throw new Exception\ApiException('Missing concept', 400);
        }
        if (!isset($body['related'])) {
            throw new Exception\ApiException('Missing related', 400);
        }
        if (!isset($body['type'])) {
            throw new Exception\ApiException('Missing type', 400);
        }
        * 
        *
        
        $user = $this->getUserByKey($body['key']);

        $concept = $this->manager->fetchByUri($body['concept']);
        $this->resourceEditAllowed($concept, $concept->getInstitution(), $user);

        $relatedConcept = $this->manager->fetchByUri($body['concept']);
        $this->resourceEditAllowed($relatedConcept, $relatedConcept->getInstitution(), $user);
        
        try {
            $this->manager->addRelation($body['concept'], $body['type'], $body['related']);
        } catch (\Exception $exc) {
            throw new Exception\ApiException($exc->getMessage(), 500);
        }
        * */
        
         if (!isset($request['key'])) {
            throw new Exception\ApiException('Missing key', 400);
        }
        if (!isset($request['concept'])) {
            throw new Exception\ApiException('Missing concept', 400);
        }
        if (!isset($request['related'])) {
            throw new Exception\ApiException('Missing related', 400);
        }
        if (!isset($request['type'])) {
            throw new Exception\ApiException('Missing type', 400);
        }
      
        $user = $this->getUserByKey($request['key']);

        $concept = $this->manager->fetchByUri($request['concept']);
        $this->resourceEditAllowed($concept, $concept->getInstitution(), $user);

        $relatedConcept = $this->manager->fetchByUri($request['concept']);
        $this->resourceEditAllowed($relatedConcept, $relatedConcept->getInstitution(), $user);
        
        try {
            $this->manager->addRelation($request['concept'], $request['type'], $request['related']);
        } catch (\Exception $exc) {
            throw new Exception\ApiException($exc->getMessage(), 500);
        }
        
    }
    
    /**
     * Delete concept relation
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @throws Exception\ApiException
     */
    protected function deleteConceptRelation(\Psr\Http\Message\ServerRequestInterface $request)
    {
        $body = $request->getParsedBody();

        if (!isset($body['key'])) {
            throw new Exception\ApiException('Missing relation', 400);
        }
        if (!isset($body['concept'])) {
            throw new Exception\ApiException('Missing concept', 400);
        }
        if (!isset($body['related'])) {
            throw new Exception\ApiException('Missing related', 400);
        }
        if (!isset($body['type'])) {
            throw new Exception\ApiException('Missing type', 400);
        }
        
        $user = $this->getUserByKey($body['key']);

        $concept = $this->manager->fetchByUri($body['concept']);
        $this->resourceEditAllowed($concept, $concept->getInstitution(), $user);

        $relatedConcept = $this->manager->fetchByUri($body['concept']);
        $this->resourceEditAllowed($relatedConcept, $relatedConcept->getInstitution(), $user);
        
        try {
            $this->manager->deleteRelation($body['concept'], $body['type'], $body['related']);
        } catch (\Exception $exc) {
            throw new Exception\ApiException($exc->getMessage(), 500);
        }
    }
    
    private function createRelationTriples($response, $relType){
        $result = [];
        foreach ($response as $key => $value) {
            $triple = [];
            $triple["s"]=$value->s->getUri();
            $triple["p"]=$relType;
            $triple["o"]=$value->o->getUri();
           array_push($result, $triple);
        }
        return $result;
    }
}
