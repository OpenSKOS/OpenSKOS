<?php

namespace OpenSkos2\Api;

use DOMDocument;
use Exception;
use OpenSkos2\Api\Exception\ApiException;
use OpenSkos2\Api\Exception\NotFoundException;
use OpenSkos2\Api\Transform\DataRdf;
use OpenSkos2\Converter\Text;
use OpenSkos2\Namespaces;
use OpenSkos2\Namespaces\Rdf;
use OpenSkos2\Tenant as Tenant;
use OpenSkos2\Validator\Resource as ResourceValidator;
use OpenSKOS_Db_Table_Row_User;
use Psr\Http\Message\ServerRequestInterface;
use Solarium\Exception\InvalidArgumentException;
use Zend\Diactoros\Response;
use Zend\Diactoros\Stream;
use OpenSkos2\Rdf\Uri;
use OpenSkos2\Namespaces\OpenSkos;

abstract class AbstractTripleStoreResource {

    use \OpenSkos2\Api\Response\ApiResponseTrait;

    protected $manager;

    public function getManager() {
        return $this->manager;
    }

    // Id is either an URI or uuid
    public function findResourceById(ServerRequestInterface $request) {
        try {
            $params = $request->getQueryParams();
            $resource = null;
            if (isset($params['id'])) {
                $id = $params['id'];
                if (substr($id, 0, 7) === "http://" || substr($id, 0, 8) === "https://") {
                    $resource = $this->manager->fetchByUri($id);
                } else {
                    $resource = $this->manager->fetchByUuid($id);
                }
            } else {
                throw new InvalidArgumentException('No Id (URI or UUID) is given');
            }


            $format = 'rdf';
            if (isset($params['format'])) {
                $format = $params['format'];
            }
            switch ($format) {
                case 'json':
                    $prep = (new \OpenSkos2\Api\Transform\DataArray($resource, []))->transform();
                    $response = $this->getSuccessResponse(json_encode($prep, JSON_UNESCAPED_SLASHES), 200, 'application/json');
                    break;
                case 'rdf':
                    $rdf = (new DataRdf($resource, true, []))->transform();
                    $response = $this->getSuccessResponse($rdf, 200);
                    break;
                default:
                    throw new InvalidArgumentException('Invalid context: ' . $context);
            }

            return $response;
        } catch (Exception $ex) {
            return $this->getErrorResponse(500, $ex->getMessage());
        }
    }

    public function create(ServerRequestInterface $request) {
        try {
            $response = $this->handleCreate($request);
        } catch (ApiException $ex) {
            return $this->getErrorResponse($ex->getCode(), $ex->getMessage());
        }
        return $response;
    }

    private function handleCreate(ServerRequestInterface $request) {
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
            $savedResource = $this->manager->fetchByUri($resourceObject->getUri());
            $rdf = (new DataRdf($savedResource, true, []))->transform();
            return $this->getSuccessResponse($rdf, 201);
        } catch (Exception $e) {
            return $this->getErrorResponse(500, $e->getMessage());
        }
    }
    
     public function update(ServerRequestInterface $request) {
        try {
            $resourceObject = $this->getResourceObjectFromRequestBody($request);
            $existingResource = $this->manager->fetchByUri((string)$resourceObject->getUri());
            $params = $request->getQueryParams($request);
            $user = $this->getUserFromParams($params);
            $resourceObject->addMetadata(array('user' => $user));
            $tenantcode = $this->getParamValueFromParams($params, 'tenant');
            $this->resourceEditAllowed($user);    

            $this->validateForUpdate($resourceObject, $tenantcode, $existingResource);
            $this->manager->replace($resourceObject);
            $savedResource = $this->manager->fetchByUri($resourceObject->getUri());
            $rdf = (new DataRdf($savedResource, true, []))->transform();
            return $this->getSuccessResponse($rdf, 201);
        } catch (Exception $e) {
            return $this->getErrorResponse(500, $e->getMessage());
        }
    }

    public function deleteResourceObject(ServerRequestInterface $request) {
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
            if (!$this->resourceDeleteAllowed($user)) {
                throw new ApiException('You do not have priority to delete this resource ' . $uri, 403);
            }

            $canBeDeleted = $this->manager->CanBeDeleted();
            if (!$canBeDeleted) {
                throw new ApiException('The resource with the ' . $uri . ' cannot be deleted. ', 403);
            }
            $this->manager->delete(new Uri($uri));
        } catch (ApiException $ex) {
            return $this->getErrorResponse($ex->getCode(), $ex->getMessage());
        }

        $xml = (new DataRdf($resourceObject))->transform();
        return $this->getSuccessResponse($xml, 202);
    }

    private function getResourceObjectFromRequestBody(ServerRequestInterface $request) {
        
        $doc = $this->getDomDocumentFromRequest($request);
        $descriptions = $doc->documentElement->getElementsByTagNameNs(Rdf::NAME_SPACE, 'Description');
        if ($descriptions->length != 1) {
            throw new InvalidArgumentException(
            'Expected exactly one '
            . '/rdf:RDF/rdf:Description, got ' . $descriptions->length, 412
            );
        }
                
        $resources = (new Text($doc->saveXML()))->getResources();
        $resource = $resources[0];
        $className = Namespaces::mapRdfTypeToClassName($this->manager->getResourceType());
        if (!isset($resource) || !$resource instanceof $className) {
            throw new InvalidArgumentException('XML Could not be converted to a ' . $className . ' object', 400);
        }

        return $resource;
    }

    private function getDomDocumentFromRequest(ServerRequestInterface $request) {
        $xml = $request->getBody();
        if (!$xml) {
            throw new InvalidArgumentException('No RDF-XML recieved', 412);
        }

        $doc = new DOMDocument();
        if (!@$doc->loadXML($xml)) {
            throw new InvalidArgumentException('Recieved RDF-XML is not valid XML', 412);
        }

        //do some basic tests
        if ($doc->documentElement->nodeName != 'rdf:RDF') {
            throw new InvalidArgumentException(
            'Recieved RDF-XML is not valid: '
            . 'expected <rdf:RDF/> rootnode, got <' . $doc->documentElement->nodeName . '/>', 412
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
        
        $uuid=$resourceObject->getProperty(OpenSkos::UUID);
            
        if ($autoGenerateIdentifiers) {
            if (!$resourceObject->isBlankNode()) {
                throw new InvalidArgumentException(
                'Parameter autoGenerateIdentifiers is set to true, but the provided '
                . 'xml already contains uri (rdf:about).', 400
                );
            };
            if (count($uuid)>0) {
                throw new InvalidArgumentException(
                'Parameter autoGenerateIdentifiers is set to true, but the provided '
                . 'xml  already contains uuid.', 400
                );
            };
        } else {
            // Is uri missing
            if ($resourceObject->isBlankNode()) {
                throw new InvalidArgumentException(
                'Uri (rdf:about) is missing from the xml. You may consider using autoGenerateIdentifiers.', 400
                );
            }
            if (count($uuid)===0) {
                throw new InvalidArgumentException(
                'OpenSkos:uuid is missing from the xml. You may consider using autoGenerateIdentifiers.', 400
                );
            };
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
        $key = $this->getParamValueFromParams($params, 'key');
        return $this->getUserByKey($key);
    }

    private function getSuccessResponse($message, $status = 200, $format="text/xml") {
        $stream = new Stream('php://memory', 'wb+');
        $stream->write($message);
        $response = (new Response($stream, $status))
                ->withHeader('Content-Type', $format . ' ; charset="utf-8"');
        return $response;
    }

    // override in superclass when necessary
    public function resourceDeleteAllowed(OpenSKOS_Db_Table_Row_User $user) {
        return $user->role == 'admin';
    }
    
    // override in superclass when necessary
    public function resourceEditAllowed(OpenSKOS_Db_Table_Row_User $user) {
        return $user->role == 'admin';
    }

    //override in superclass when necessary 
    protected function validate($resourceObject, $tenantcode) {
        $validator = new ResourceValidator($this->manager, new Tenant($tenantcode));
        if (!$validator->validate($resourceObject)) {
            //var_dump($validator->getErrorMessages());
            throw new InvalidArgumentException(implode(' ', $validator->getErrorMessages()), 400);
        }
        $uuid = $resourceObject -> getProperty(OpenSkos::UUID);
        $resType = $this->manager -> getResourceType();
        $resources= $this -> manager -> fetchSubjectWithPropertyGiven(OpenSkos::UUID, $uuid[0], $resType);
        if (count($resources)>0) {
           throw new ApiException('The resource with the uuid ' . $uuid[0] . ' has been already registered.', 400);
       }
    }
    
    //override in superclass when necessary 
    protected function validateForUpdate($resourceObject, $tenantcode, $existingResourceObject) {
        $this -> validate($resourceObject, $tenantcode);
         // do not update uuid: it must be intact forever, connected to uri
        $uuid = $resourceObject->getProperty(OpenSkos::UUID);
        $oldUuid = $existingResourceObject ->getProperty(OpenSkos::UUID);
        if ($uuid[0]->getValue() !== $oldUuid[0]->getValue()) {
            throw new ApiException('You cannot change UUID of the resouce. Keep it ' . $oldUuid[0], 400);
        }
    }

}
