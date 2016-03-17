<?php

namespace OpenSkos2\Api;


use OpenSkos2\Api\Exception\ApiException;
use OpenSkos2\Api\Exception\InvalidArgumentException;
use OpenSkos2\Api\Exception\NotFoundException;
use OpenSkos2\Api\Transform\DataRdf;
use OpenSkos2\Converter\Text;
use OpenSkos2\Namespaces;
use OpenSkos2\Namespaces\Rdf;
use OpenSkos2\Tenant as Tenant;
use OpenSkos2\Validator\Resource as ResourceValidator;
use OpenSKOS_Db_Table_Row_User;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\Stream;
use Zend\Diactoros\Uri;

abstract class AbstractTripleStoreResource
{
    use \OpenSkos2\Api\Response\ApiResponseTrait;
    
    protected $manager; 
    
    public function getManager(){
        return $this -> manager;
    }
    
   
    public function create(ServerRequestInterface $request) {
        //var_dump($this->manager);
        try {
            $response = $this->handleCreate($request);
        } catch (ApiException $ex) {
            return $this->getErrorResponse($ex->getCode(), $ex->getMessage());
        }
        return $response;
    }
    
    private function handleCreate(ServerRequestInterface $request)
    {
        try {
            $resourceObject = $this->getResourceObjectFromRequestBody($request);

            if (!$resourceObject->isBlankNode() && $this->manager->askForUri((string) $resourceObject->getUri())) {
                throw new InvalidArgumentException(
                'The resource with uri ' . $resourceObject->getUri() . ' already exists. Use PUT instead.', 400
                );
            }

            $params = $request->getQueryParams($request);
            $user = $this->getUserFromParams($params);
            $resourceObject->addMetadata(array('user' => $user));
            $autoGenerateUri = $this->checkResourceIdentifiers($request, $resourceObject);
            if ($autoGenerateUri) {
                $tenantcode = $this->getParamValueFromParams($params, 'tenant');
                $resourceObject->selfGenerateUri($tenantcode, $this->manager);
            };
            $this->validate($resourceObject, $tenantcode);
            $this->manager->insert($resourceObject);
            $savedResource=$this->manager->fetchByUri($resourceObject->getUri());
            //var_dump($savedResource);
            $rdf = (new \OpenSkos2\Api\Transform\DataRdf($savedResource, true, []))->transform();
            return $this->getSuccessResponse($rdf, 201);
        } catch (\Exception $e) {
            return $this->getSuccessResponse($e, 200);
        }
    }
    
    public function deleteResourceObject(ServerRequestInterface $request)
    {
        try {
            $params = $request->getQueryParams();
            if (empty($params['uri'])) {
                throw new InvalidArgumentException('Missing uri parameter');
            }
            
            $uri = $params['uri'];
            $resourceObject = $this->manager->fetchByUri($uri);
            if (!$resourceObject) {
                throw new NotFoundException('The resource is not found by uri :' . $uri, 404);
            }
            
            $user = $this->getUserFromParams($params);
            if  (!$this->resourceDeleteAllowed($user)) {
                 throw new ApiException('You do not have priority to delete rgis resource ' . $uri, 403);
            }
            
            $canBeDeleted = $this->manager->CanBeDeleted();
            if  (!$canBeDeleted) {
                 throw new ApiException('The resource with the ' . $uri . ' cannot be deleted. ', 403);
            }
            $this->manager->delete(new Uri($uri));
        } catch (ApiException $ex) {
            return $this->getErrorResponse($ex->getCode(), $ex->getMessage());
        }
        
        $xml = (new DataRdf($resourceObject))->transform();
        return $this->getSuccessResponse($xml, 202);
    }
    
    
    private function getResourceObjectFromRequestBody(ServerRequestInterface $request)
    {
        $doc = $this->getDomDocumentFromRequest($request);
        $descriptions = $doc->documentElement->getElementsByTagNameNs(Rdf::NAME_SPACE, 'Description');
        if ($descriptions->length != 1) {
            throw new InvalidArgumentException(
                'Expected exactly one '
                . '/rdf:RDF/rdf:Description, got '.$descriptions->length, 412
            );
        }

        $resources = (new Text($doc->saveXML()))->getResources();
        $resource = $resources[0];
        //var_dump($resource);
        $className= Namespaces::mapRdfTypeToClassName($this->manager->getResourceType());
        if (!isset($resource) || !$resource instanceof $className) {
            throw new InvalidArgumentException('XML Could not be converted to a ' . $className . ' object', 400);
        }

        return $resource;
    }
    
   
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
    
    
     private function checkResourceIdentifiers(ServerRequestInterface $request, $resourceObject) {
        $params = $request->getQueryParams();

        // We return if an uri must be autogenerated
        $autoGenerateIdentifiers = false;
        if (!empty($params['autoGenerateIdentifiers'])) {
            $autoGenerateIdentifiers = filter_var(
                    $params['autoGenerateIdentifiers'], FILTER_VALIDATE_BOOLEAN
            );
        }

        if ($autoGenerateIdentifiers) {
            if (!$resourceObject->isBlankNode()) {
                throw new InvalidArgumentException(
                'Parameter autoGenerateIdentifiers is set to true, but the '
                . 'xml already contains uri (rdf:about).', 400
                );
            }
        } else {
            // Is uri missing
            if ($resourceObject->isBlankNode()) {
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
        $key = $this -> getParamValueFromParams($params, 'key');
        return $this->getUserByKey($key);
    }
    
    private function getSuccessResponse($message, $status = 200)
    {
        $stream = new Stream('php://memory', 'wb+');
        $stream->write($message);
        $response = (new Response($stream, $status))
            ->withHeader('Content-Type', 'text/xml; charset="utf-8"');
        return $response;
    }
    
    public function resourceDeleteAllowed(OpenSKOS_Db_Table_Row_User $user) {
        return $user -> role == 'admin';
    }
    
    protected function validate($resourceObject, $tenantcode)
    {
        $validator = new ResourceValidator($this->manager, new Tenant($tenantcode));
        if (!$validator->validate($resourceObject)) {
            //var_dump($validator->getErrorMessages());
            throw new InvalidArgumentException(implode(' ', $validator->getErrorMessages()), 400);
        }
    }

}