<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace OpenSkos2\Api;

use OpenSkos2\Api\Exception\ApiException;
use OpenSkos2\Api\Exception\NotFoundException;
use OpenSkos2\Converter\Text;
use OpenSkos2\Namespaces;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Namespaces\Rdf;
use OpenSkos2\Api\Exception\InvalidArgumentException;
use OpenSkos2\Api\Response\ResultSet\JsonResponse;
use OpenSkos2\Api\Response\ResultSet\JsonpResponse;
use OpenSkos2\Api\Response\ResultSet\RdfResponse;
use OpenSkos2\Api\Response\Detail\JsonResponse as DetailJsonResponse;
use OpenSkos2\Api\Response\Detail\JsonpResponse as DetailJsonpResponse;
use OpenSkos2\Api\Response\Detail\RdfResponse as DetailRdfResponse;
use OpenSkos2\Api\Exception\InvalidPredicateException;
use OpenSkos2\SkosCollectionManager;
use OpenSkos2\FieldsMaps;
use OpenSkos2\Validator\Resource as ResourceValidator;
use OpenSkos2\Tenant as Tenant;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\Stream;
use OpenSKOS_Db_Table_Row_User;
use OpenSkos2\Rdf\Uri;
 /**
     * Resource manager
     *
     * @var \OpenSkos2\Rdf\ResourceManager
     */
class SkosCollection
{
    use \OpenSkos2\Api\Response\ApiResponseTrait;
    
    private $manager;
    
    public function __construct(SkosCollectionManager $manager) {
        $this->manager = $manager;
    }
    
    public function create(ServerRequestInterface $request) {
        try {
            $response = $this->handleCreate($request);
        } catch (ApiException $ex) {
            return $this->getErrorResponse($ex->getCode(), $ex->getMessage());
        }
        return $response;
    }
    
    private function handleCreate(ServerRequestInterface $request)
    {
        $skosCollection = $this->getSkosCollectionFromRequest($request);
        
        if (!$skosCollection->isBlankNode() && $this->manager->askForUri((string)$skosCollection->getUri())) {
            throw new InvalidArgumentException(
                'The concept with uri ' . $skosCollection->getUri() . ' already exists. Use PUT instead.',
                400
            );
        }
        $params = $request->getQueryParams($request);
        $user = $this ->getUserFromParams($params);
        $skosCollection->addMetadata($user->getFoafPerson());
        $autoGenerateUri = $this->checkSkosCollectionIdentifiers($request, $skosCollection);
        if ($autoGenerateUri) {
            $tenantcode = $this->getParamValueFromParams($params, 'tenant');
            $skosCollection->selfGenerateUri($tenantcode, $this->manager);
        }
        
        //\Tools\Logging::var_error_log(" skosCollectie \n", $skosCollection, '/app/data/Logger.txt');
        // ??? what zijn de validatie criteria voor de skos:collection
        //$this->validate($skosCollection, $tenant);
        $this->manager->insert($skosCollection);
        
        $rdf = (new Transform\DataRdf($skosCollection))->transform();
        return $this->getSuccessResponse($rdf, 201);
    }
    
    public function deleteSkosCollection(ServerRequestInterface $request)
    {
        try {
            $params = $request->getQueryParams();
            if (empty($params['uri'])) {
                throw new InvalidArgumentException('Missing uri parameter');
            }
            
            $uri = $params['uri'];
            $skoscollection = $this->manager->fetchByUri($uri);
            if (!$skoscollection) {
                throw new NotFoundException('Skos collection is not found by uri :' . $uri, 404);
            }
            
            $user = $this->getUserFromParams($params);
            if  (!$this->skosCollectionDeleteAllowed($user)) {
                 throw new ApiException('You do not have priority to delete skos collection ' . $uri, 403);
            }
            
            $concepts = $this->manager->fetchConceptsForSkosCollection($uri);
            if  (count($concepts)>0) {
                 throw new ApiException('The skos collection ' . $uri . ' cannot be deleted because it contains concepts. Purge it first. ', 403);
            }
            $this->manager->delete(new Uri($uri));
        } catch (ApiException $ex) {
            return $this->getErrorResponse($ex->getCode(), $ex->getMessage());
        }
        
        $xml = (new \OpenSkos2\Api\Transform\DataRdf($skoscollection))->transform();
        return $this->getSuccessResponse($xml, 202);
    }
    
    // almost a copy of the same method from concept.php
     private function getSkosCollectionFromRequest(ServerRequestInterface $request)
    {
        $doc = $this->getDomDocumentFromRequest($request);
        $descriptions = $doc->documentElement->getElementsByTagNameNs(Rdf::NAME_SPACE, 'Description');
        if ($descriptions->length != 1) {
            throw new InvalidArgumentException(
                'Expected exactly one '
                . '/rdf:RDF/rdf:Description, got '.$descriptions->length, 412
            );
        }

        $resource = (new Text($doc->saveXML()))->getResources();
        //var_dump($resource[0]);
        
        if (!isset($resource[0]) || !$resource[0] instanceof \OpenSkos2\SkosCollection) {
            throw new InvalidArgumentException('XML Could not be converted to a SkosCollection object', 400);
        }

       $skosCollection = $resource[0];
        
        return $skosCollection;
    }
    
    // copied from the concept.php. must be refactored
    private function getDomDocumentFromRequest(ServerRequestInterface $request)
    {
        $xml = $request->getBody();
        if (!$xml) {
            throw new InvalidArgumentException('No RDF-XML recieved', 412);
        }
        
        $doc = new \DOMDocument();
        if (!@$doc->loadXML($xml)) {
            throw new InvalidArgumentException('Recieved RDF-XML is not valid XML', 412);
        }

        //do some basic tests
        if ($doc->documentElement->nodeName != 'rdf:RDF') {
            throw new InvalidArgumentException(
                'Recieved RDF-XML is not valid: '
                . 'expected <rdf:RDF/> rootnode, got <'.$doc->documentElement->nodeName.'/>',
                412
            );
        }
        //var_dump($doc);
       
        return $doc;
    }
    
    // practically a copy of the same method of the concept class. Refactor.
     private function checkSkosCollectionIdentifiers(ServerRequestInterface $request, \OpenSkos2\SkosCollection $skosCollection) {
        $params = $request->getQueryParams();

        // We return if an uri must be autogenerated
        $autoGenerateIdentifiers = false;
        if (!empty($params['autoGenerateIdentifiers'])) {
            $autoGenerateIdentifiers = filter_var(
                    $params['autoGenerateIdentifiers'], FILTER_VALIDATE_BOOLEAN
            );
        }

        if ($autoGenerateIdentifiers) {
            if (!$skosCollection->isBlankNode()) {
                throw new InvalidArgumentException(
                'Parameter autoGenerateIdentifiers is set to true, but the '
                . 'xml already contains uri (rdf:about).', 400
                );
            }
        } else {
            // Is uri missing
            if ($skosCollection->isBlankNode()) {
                throw new InvalidArgumentException(
                'Uri (rdf:about) is missing from the xml. You may consider using autoGenerateIdentifiers.', 400
                );
            }
        }

        return $autoGenerateIdentifiers;
    }

     private function getParamValueFromParams($params, $paramname) {
        if (empty($params[$paramname])) {
            throw new InvalidArgumentException('No ' . $paramname . ' specified', 412);
        }
        return $params[$paramname];
    }
    
      private function getUserFromParams($params) {
        if (empty($params['key'])) {
            throw new InvalidArgumentException('No key specified', 412);
        }
        return $this->getUserByKey($params['key']);
    }
    
    private function getSuccessResponse($message, $status = 200)
    {
        $stream = new Stream('php://memory', 'wb+');
        $stream->write($message);
        $response = (new Response($stream, $status))
            ->withHeader('Content-Type', 'text/xml; charset="utf-8"');
        return $response;
    }
    
    // not fully implemented yet 
    public function skosCollectionDeleteAllowed(OpenSKOS_Db_Table_Row_User $user) {
        return $user -> role == 'admin';
    }

}