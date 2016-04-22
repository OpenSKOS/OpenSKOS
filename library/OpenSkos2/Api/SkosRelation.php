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
use OpenSkos2\Namespaces\Rdf;
use Psr\Http\Message\ServerRequestInterface as PsrServerRequestInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\Response\JsonResponse as JsonResponse2;
use Zend\Diactoros\Stream;

require_once dirname(__FILE__) .'/../config.inc.php';

class SkosRelation extends AbstractTripleStoreResource {

    use \OpenSkos2\Api\Response\ApiResponseTrait;
    
    public function __construct(ConceptManager $manager)
    {
        $this->manager = $manager;
    }
    
    public function findAllPairsForSkosRelationType($request) {
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
            $response = $this->manager->fetchAllRelationsOfType(Skos::NAME_SPACE, $relType, $sourceSchemata, $targetSchemata);
            //var_dump($response);
            $intermediate = $this->createOutputRelationTriples($response);
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
    
   
    
    
    public function findSkosRelatedConcepts($request, $uri) {
        $params = $this->getAndAdaptQueryParams($request);
        $relType = Skos::NAME_SPACE . $params['relationType'];
        if (isset($params['inSchema'])) {
            $schema = $params['inSchema'];
        } else {
            $schema = null;
        }
        try {
            $concepts = $this->manager->fetchRelations($uri, $relType, $schema);
            //var_dump($concepts);
            $result = new ResourceResultSet($concepts, $concepts->count(), 0, MAXIMAL_ROWS);
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
   public function addSkosRelation(PsrServerRequestInterface $request)
    //public function addRelation($request)
    {
        $params=$this -> getAndAdaptQueryParams($request);
        try {
            $this->addConceptSkosRelation($request);
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
    public function deleteSkosRelation(PsrServerRequestInterface $request)
    {
        try {
            $this->deleteConceptSkosRelation($request);
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
    protected function addConceptSkosRelation(PsrServerRequestInterface $request)
    {
        try {
            $body = $this -> preEditChecksSkosRels($request);
            
            $this->manager->addSkosRelation($body['concept'], $body['type'], $body['related']);
        } catch (Exception$exc) {
            throw new ApiException($exc->getMessage(), $exc->getCode());
        }
        
        
    }
    
    /**
     * Delete concept relation
     *
     * @param PsrServerRequestInterface $request
     * @throws ApiException
     */
    protected function deleteConceptSkosRelation(PsrServerRequestInterface $request)
    {
       try {
            $body = $this -> preEditChecksSkosRels($request);
            $this->manager->deleteSkosRelation($body['concept'], $body['type'], $body['related']);
        } catch (Exception$exc) {
            throw new ApiException($exc->getMessage(), 500);
        }
    }
    
    
    private function preEditChecksSkosRels(PsrServerRequestInterface $request) {

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

        $count1 = $this->manager->countTriples('<' . $body['concept'] . '>', '<' . Rdf::TYPE . '>', '<' . Skos::CONCEPT . '>');
        if ($count1 < 1) {
            throw new ApiException('The concept referred by the uri ' . $body['concept'] . ' does not exist.', 400);
        }
        $count2 = $this->manager->countTriples('<' . $body['related'] . '>', '<' . Rdf::TYPE . '>', '<' . Skos::CONCEPT . '>');
        if ($count2 < 1) {
            throw new ApiException('The concept referred by the uri ' . $body['related'] . ' does not exist.', 400);
        }

        $user = $this->getUserByKey($body['key']);

        $concept = $this->manager->fetchByUri($body['concept']);
        $concept->editingAllowed($user, $this->tenant);

        $relatedConcept = $this->manager->fetchByUri($body['related']);
        $relatedConcept->editingAllowed($user, $this->tenant);

        return $body;
    }

    public function listAllSkosRelations(){
         $intermediate = Skos::getSkosRelationsTypes();
         $result = new JsonResponse2($intermediate);
         return $result;
    }
    
  
    
  
 
}
