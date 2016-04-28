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
use OpenSkos2\RelationManager;
use OpenSkos2\Concept;
use OpenSkos2\Tenant;
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Namespaces\Rdf;
use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Namespaces\Owl;
use Psr\Http\Message\ServerRequestInterface as PsrServerRequestInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\Response\JsonResponse as JsonResponse2;
use Zend\Diactoros\Stream;
use OpenSkos2\Api\Response\ResultSet\JsonResponse;
use OpenSkos2\Api\Response\ResultSet\JsonpResponse;
use OpenSkos2\Api\Response\ResultSet\RdfResponse;
use OpenSkos2\Validator\Resource as ResourceValidator;

require_once dirname(__FILE__) .'/../config.inc.php';

class Relation extends AbstractTripleStoreResource {

    
    public function __construct(RelationManager $manager)
    {
        $this->manager = $manager;
    }
    
    
    
    public function findAllPairsForRelation($request) {
        //public function findAllPairsForType(ServerRequestInterface $request)
        $params=$request->getQueryParams();
        $relType = $params['id'];
        $sourceSchemata = null;
        $targetSchemata = null;
        if (isset($params['sourceSchemata'])) {
            $sourceSchemata = $params['sourceSchemata'];
        };
        if (isset($params['targetSchemata'])) {
            $targetSchemata = $params['targetSchemata'];
        }; 
        try {
            $response = $this->manager->fetchAllRelationsOfType($relType, $sourceSchemata, $targetSchemata);
            $intermediate = $this->manager->createOutputRelationTriples($response);
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
    
    public function findRelatedConcepts($request, $uri, $format) {
        $params=$request->getQueryParams();
        $relType = $params['id'];
        if (isset($params['inSchema'])) {
            $schema = $params['inSchema'];
        } else {
            $schema = null;
        }
        try {
            $concepts = $this->manager->fetchRelationsForConcept($uri, $relType, $schema);
            $result = new ResourceResultSet($concepts, $concepts->count(), 0, MAXIMAL_ROWS);
            switch ($format) {
            case 'json':
                $response = (new JsonResponse($result, []))->getResponse();
                break;
            case 'jsonp':
                $response = (new JsonpResponse($result, $params['callback'], []))->getResponse();
                break;
            case 'rdf':
                $response = (new RdfResponse($result, []))->getResponse();
                break;
            default:
                throw new  ApiException('Invalid context: ' . $format, 400);
        }
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
    {
        $params=$this -> getAndAdaptQueryParams($request);
        try {
            $body = $this -> preEditChecksRels($request);
            $this->manager->addRelation($body['concept'], $body['type'], $body['related']);
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
            $params = $this->getAndAdaptQueryParams($request); // sets tenant info
            $body = $this -> preEditChecksRels($request);
            $this->manager->deleteRelation($body['concept'], $body['type'], $body['related']);
        } catch (ApiException $exc) {
            return $this->getErrorResponse($exc->getCode(), $exc->getMessage());
        }

        $stream = new Stream('php://memory', 'wb+');
        $stream->write('Relation deleted');
        $response = (new Response())
            ->withBody($stream);
        return $response;
    }
    
    
   
    private function preEditChecksRels(PsrServerRequestInterface $request) {

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

        $concept = $this->manager->fetchByUri($body['concept'], Concept::TYPE);
        $concept->editingAllowed($user, $this->tenant);
        $relatedConcept = $this->manager->fetchByUri($body['related'], Concept::TYPE);
        $relatedConcept->editingAllowed($user, $this->tenant);

        return $body;
    }

   
    // used when creating a user-defined relation
    protected function checkResourceIdentifiers(PsrServerRequestInterface $request, $resourceObject) {
        if ($resourceObject->isBlankNode()) {
            throw new ApiException(
            'Uri (rdf:about) is missing from the xml. For user relations you must supply it, autogenerateIdentifiers is set to false compulsory.', 400
            );
        }

       $ttl = $resourceObject->getUri();
       $hakje = strrpos($ttl, "#");
       if (strpos($ttl, 'http://') !== 0 || !$hakje || ($hakje === strlen($ttl)-1)) {
            throw new ApiException('The user-defined relation uri must have the form <namespace>#<name> where <namespace> starts with http:// and name is not empty.', 400);
        
       }
        // do not generate idenitifers
        return false;
    }
    
     // used when creating a user-defined relation
     protected function validate($resourceObject, $tenant) {
       $validator = new ResourceValidator($this->manager, new Tenant($tenant['code']));
       if (!$validator->validate($resourceObject)) {
            throw new ApiException(implode(' ', $validator->getErrorMessages()), 400);
        }
       //must have new title
       $this->validatePropertyForCreate($resourceObject, DcTerms::TITLE, Owl::OBJECT_PROPERTY);
       
       return true;
    }
    
    
    // used when updating a user-defined relation
    protected function validateForUpdate($resourceObject, $tenant,  $existingResourceObject) {
        parent::validateForUpdate($resourceObject, $tenant, $existingResourceObject);
       // must not occur as another collection's name if different from the old one 
        $this->validatePropertyForUpdate($resourceObject, $existingResourceObject, DcTerms::TITLE, Owl::OBJECT_PROPERTY);
    
    }
    
    
 
}
