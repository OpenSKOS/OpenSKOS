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

use Exception;
use OpenSkos2\Api\Exception\ApiException;
use OpenSkos2\Api\Response\ResultSet\JsonResponse;
use OpenSkos2\ConceptManager;
use OpenSkos2\Namespaces\Skos;
use Psr\Http\Message\ServerRequestInterface as PsrServerRequestInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\Response\JsonResponse as JsonResponse2;
use Zend\Diactoros\Stream;

class Relation
{
    use \OpenSkos2\Api\Response\ApiResponseTrait;
    /**
     * @var ConceptManager
     */
    protected $manager;

    /**
     * @param ConceptManager $manager
     */
    public function __construct(ConceptManager $manager)
    {
        $this->manager = $manager;
    }
    
    public function findAllPairsForRelationType($request) {
        //public function findAllPairsForType(ServerRequestInterface $request)
        $params=$request->getQueryParams();
        $relType = $params['q'];
        $sourceSchemata = null;
        $targetSchemata = null;
        if (isset($params['sourceSchemata'])) {
            $sourceSchemata = $params['sourceSchemata'];
        };
        if (isset($params['targetSchemata'])) {
            $targetSchemata = $params['targetSchemata'];
        }; 
        try {
            $response = $this->manager->fetchAllRelations($relType, $sourceSchemata, $targetSchemata);
            //var_dump($response);
            $intermediate = $this->createRelationTriples($response);
            $result = new JsonResponse2($intermediate);
            return $result;
        } catch (Exception $exc) {
            if ($exc instanceof ApiException) {
                return $this->getErrorResponse($exc->getCode(), $exc->getMessage());
            } else {
                return $this->getErrorResponse(500, $exc->getMessage());
            }
        }
    }
    
    public function findRelatedConcepts($request, $uri) {
        $params = $request->getQueryParams();
        $relType = Skos::NAME_SPACE . $params['relationType'];
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
            if ($exc instanceof ApiException) {
                return $this->getErrorResponse($exc->getCode(), $exc->getMessage());
            } else {
                return $this->getErrorResponse(500, $exc->getMessage());
            }
        }
    }
    

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return Response
     */
   public function addRelation(PsrServerRequestInterface $request)
    //public function addRelation($request)
    {
        try {
            $this->addConceptRelation($request);
        } catch (ApiException $exc) {
            return $this->getErrorResponse($exc->getCode(), $exc->getMessage());
        }

        $stream = new Stream('php://memory', 'wb+');
        $stream->write('Relations added');
        $response = (new Response())
                ->withBody($stream);
        return $response;
    }
    
    /**
     * @param PsrServerRequestInterface $request
     * @return Response
     */
    public function deleteRelation(PsrServerRequestInterface $request)
    {
        try {
            $this->deleteConceptRelation($request);
        } catch (ApiException $exc) {
            return $this->getErrorResponse($exc->getCode(), $exc->getMessage());
        }

        $stream = new Stream('php://memory', 'wb+');
        $stream->write('Relation deleted');
        $response = (new Response())
            ->withBody($stream);
        return $response;
    }
    
    /**
     * Add concept relation
     *
     * @param PsrServerRequestInterface $request
     * @throws ApiException
     */
    protected function addConceptRelation(PsrServerRequestInterface $request)
    {
        try {
            $body = $this -> preEditChecks($request);
            $this->manager->addRelation($body['concept'], $body['type'], $body['related']);
        } catch (Exception$exc) {
            throw new ApiException($exc->getMessage(), 500);
        }
        
        
    }
    
    /**
     * Delete concept relation
     *
     * @param PsrServerRequestInterface $request
     * @throws ApiException
     */
    protected function deleteConceptRelation(PsrServerRequestInterface $request)
    {
       try {
            $body = $this -> preEditChecks($request);
            $this->manager->deleteRelation($body['concept'], $body['type'], $body['related']);
        } catch (Exception$exc) {
            throw new ApiException($exc->getMessage(), 500);
        }
    }
    
    
    private function preEditChecks(PsrServerRequestInterface $request){
        
        $body = $request->getParsedBody();
        
        if (!isset($body['key'])) {
            throw new ApiException('Missing key', 400);
        }
        if (!isset($body['concept'])) {
            throw new ApiException('Missing concept', 400);
        }
        if (!isset($body['related'])) {
            throw new ApiException('Missing related', 400);
        }
        if (!isset($body['type'])) {
            throw new ApiException('Missing type', 400);
        }
        
        if (!isset($body['tenant'])) {
            throw new ApiException('Missing tenant (code)', 400);
        }
        
        $user = $this->getUserByKey($body['key']);

        $concept = $this->manager->fetchByUri($body['concept']);
        $concept->editingAllowed($user, $body['tenant']);

        $relatedConcept = $this->manager->fetchByUri($body['related']);
        $relatedConcept->editingAllowed($user, $body['tenant']);
        
        return $body;
    }
    
    
    public function listAllRelations(){
         $intermediate = Skos::getRelationsTypes();
         $result = new JsonResponse2($intermediate);
         return $result;
    }
    
    private function createRelationTriples($response){
        $result = [];
        foreach ($response as $key => $value) {
            $subject = array("uuid" => $value->s_uuid->getValue(), "prefLabel" => $value->s_prefLabel->getValue(), "lang" => $value->s_prefLabel->getLang(), "schema"=>$value->s_schema->getUri());
            $object = array("uuid" => $value->o_uuid->getValue(), "prefLabel" => $value->o_prefLabel->getValue(), "lang" => $value->o_prefLabel->getLang(), "schema" => $value -> o_schema ->getUri());
            $triple=array("s" => $subject, "p" => $value -> p -> getUri(), "o"=>$object);
           array_push($result, $triple);
        }
        return $result;
    }
}
