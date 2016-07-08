<?php

namespace OpenSkos2\Api;

use DOMDocument;
use Exception;
use OpenSkos2\Preprocessor;
use OpenSkos2\Api\Exception\ApiException;
use OpenSkos2\Api\Transform\DataRdf;
use OpenSkos2\Converter\Text;
use OpenSkos2\Namespaces;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Namespaces\Rdf;
use OpenSkos2\Namespaces\Dcmi;
use OpenSkos2\Rdf\Uri;
use OpenSkos2\Validator\Resource as ResourceValidator;
use OpenSKOS_Db_Table_Row_User;
use OpenSKOS_Db_Table_Users;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\Stream;
use OpenSkos2\Api\Response\Detail\JsonpResponse as DetailJsonpResponse;
use OpenSkos2\Api\Response\Detail\JsonResponse as DetailJsonResponse;
use OpenSkos2\Api\Response\Detail\RdfResponse as DetailRdfResponse;
use OpenSkos2\Api\Response\ResultSet\JsonpResponse;
use OpenSkos2\Api\Response\ResultSet\JsonResponse;
use OpenSkos2\Api\Response\ResultSet\RdfResponse;
use OpenSkos2\Api\ResourceResultSet;

require_once dirname(__FILE__) .'/../config.inc.php';

abstract class AbstractTripleStoreResource {

  
    protected $manager;
    protected $authorisationManager;
    protected $deletionManager;
    
    public function getManager() {
        return $this->manager;
    }
    
   
    
    protected function getAndAdaptQueryParams(ServerRequestInterface $request) {
        $queryparams = $request->getQueryParams();
        if (empty($queryparams['tenant'])) {
            throw new ApiException('No tenant specified', 412);
        };
        $tenantUri = $this->manager->fetchInstitutionUriByCode($queryparams['tenant']);
        if ($tenantUri === null) {
            throw new ApiException('The tenant referred by code ' . $queryparams['tenant'] . ' does not exist in the triple store. You may want to set CHECK_MYSQL to true and allow search in the mysql database.', 400);
        }
        $params = $queryparams;
        $params['tenantcode'] = $params['tenant']; 
        $params['tenanturi'] = $tenantUri;
        return $params;
    }

    public function mapNameSearchID() {
        $index =  $this->manager->fetchNameUri();
        return $index;
    }

    
    public function fetchDeatiledListResponse($params) {
        try {
        $index = $this ->fetchDetailedList($params);
        
        foreach ($index as $resource) {
            $resource = $this->manager->augmentResourceWithTenant($resource);
        }
        
        $result = new ResourceResultSet($index, count($index), 1, MAXIMAL_ROWS);
        switch ($params['context']) {
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
                    throw new ApiException('Invalid context: ' . $params['context'], 400);
        }
        return $response;
        } catch (Exception $e) {
            $code = $e->getCode();
            if ($code === 0 || $code=== null) {
                $code = 500;
            }
            return $this->getErrorResponse($code, $e->getMessage());
        }
    }

    private function fetchDetailedList($params) {
        $resType = $this->manager->getResourceType();
        if ($resType === Dcmi::DATASET && $params['allow_oai'] !== null) {
            $index = $this->manager->fetch([OpenSkos::ALLOW_OAI => new \OpenSkos2\Rdf\Literal($params['allow_oai'], null, \OpenSkos2\Rdf\Literal::TYPE_BOOL),]);
        } else {
            $index = $this->manager->fetch();
        }
        return $index;
    }

// Id is either an URI or uuid
    public function findResourceByIdResponse(ServerRequestInterface $request, $id, $context) {
        try {
            $params = $request->getQueryParams();
            if (isset($params['id'])) {
                $id = $params['id'];
            };
            $resource = $this->findResourcebyId($id);
            if (isset($context)) {
                $format = $context;
            } else {
                if (isset($params['format'])) {
                    $format = $params['format'];
                } else {
                    $format = "rdf";
                }
            }
            switch ($format) {
                case 'json':
                    $response = (new DetailJsonResponse($resource, []))->getResponse();
                    break;
                case 'jsonp':
                    $response = (new DetailJsonpResponse($resource, $params['callback'], []))->getResponse();
                    break;
                case 'rdf':
                    $response = (new DetailRdfResponse($resource, []))->getResponse();
                    break;
                default:
                    throw new ApiException('Invalid context: ' . $context, 400);
            }

            return $response;
        } catch (Exception $e) {
            $code = $e->getCode();
            if ($code === 0 || $code=== null) {
                $code = 500;
            } 
            return $this->getErrorResponse($code, $e->getMessage());
        }
    }

    // Id is either an URI or uuid
    public function findResourceById($id) {
       return $this->manager->findResourceById($id, $this->manager->getResourceType());
    }

    public function create(ServerRequestInterface $request) {
       
        try {
            $params = $this->getAndAdaptQueryParams($request);
            $user = $this->getUserFromParams($params);
            $resourceObject = $this->getResourceObjectFromRequestBody($request);
            
            if (!$this->authorisationManager->resourceCreationAllowed($user, $params['tenantcode'], $resourceObject)) {
                throw new ApiException('You do not have rights to create resource of type '. $this->getManager()->getResourceType() . " in tenant " . $params['tenantcode'] . '. Your role is "' .  $user->role . '"', 403); 
            }
            if (!$resourceObject->isBlankNode() && $this->manager->askForUri((string) $resourceObject->getUri())) {
                throw new ApiException(
                'The resource with uri ' . $resourceObject->getUri() . ' already exists. Use PUT instead.', 400
                );
            }

            $autoGenerateUri = $this->checkResourceIdentifiers($request, $resourceObject);
            $preprocessor = new Preprocessor($this->manager, $this->manager->getResourceType(), $user->getFoafPerson()->getUri());
            $preprocessedResource = $preprocessor -> forCreation($resourceObject, $params, $autoGenerateUri);
            $this->validate($preprocessedResource, false, $params['tenanturi']);
            $this->manager->insert($preprocessedResource);
            $savedResource = $this->manager->fetchByUri($resourceObject->getUri());
            $rdf = (new DataRdf($savedResource, true, []))->transform();
            return $this->getSuccessResponse($rdf, 201);
        } catch (Exception $e) {
            $code = $e->getCode();
            if ($code === 0 || $code=== null) {
                $code = 500;
            } 
            return $this->getErrorResponse($code, $e->getMessage());
        }
    }
    
    public function update(ServerRequestInterface $request) {
        try {
            $resourceObject = $this->getResourceObjectFromRequestBody($request);
            if ($resourceObject->isBlankNode()) {
                throw new ApiException("Missed uri (rdf:about)!", 400);
            }
            $params = $this->getAndAdaptQueryParams($request);
            $user = $this->getUserFromParams($params);
            
            $uri = $resourceObject->getUri();
            $preprocessor = new Preprocessor($this->manager, $this->manager->getResourceType(), $params['tenantcode'], $user->getFoafPerson()->getUri());
            $preprocessedResource = $preprocessor ->forUpdate($resourceObject, $params);
            if ($this->authorisationManager->resourceEditAllowed($user, $params['tenantcode'], $existingResource)) {
                $this->validate($preprocessedResource, true, $params['tenanturi']);
                $this->manager->replace($preprocessedResource);
                $savedResource = $this->manager->fetchByUri($resourceObject->getUri());
                $rdf = (new DataRdf($savedResource, true, []))->transform();
                return $this->getSuccessResponse($rdf, 201);
            } else {
                throw new ApiException('You do not have rights to edit resource ' . $uri  . '. Your role is "' . $user->role . '" in tenant ' . $params['tenantcode'], 403);
            }
        } catch (Exception $e) {
            $code = $e->getCode();
            if ($code === 0 || $code=== null) {
                $code = 500;
            } 
            return $this->getErrorResponse($code, $e->getMessage());
        }
    }

    public function deleteResourceObject(ServerRequestInterface $request) {
        try {
            $params = $this->getAndAdaptQueryParams($request);
            if (empty($params['uri'])) {
                throw new ApiException('Missing uri parameter', 400);
            }

            $uri = $params['uri'];
            $resourceObject = $this->manager->fetchByUri($uri);
            if (!$resourceObject) {
                throw new ApiException('The resource is not found by uri :' . $uri, 404);
            }

            $user = $this->getUserFromParams($params);
            if (!$this->authorisationManager->resourceDeleteAllowed($user, $params['tenantcode'], $resourceObject)) {
                 throw new ApiException('You do not have rights to delete resource ' . $uri  . '. Your role is "' . $user->role . '" in tenant ' . $params['tenantcode'], 403);
            }

            $canBeDeleted = $this->deletionManager->canBeDeleted($uri, $this->manager);
            if (!$canBeDeleted) {
                throw new ApiException('The resource with the ' . $uri . ' cannot be deleted. Check if there are other resources referring to it. ', 412);
            }
            $this->manager->delete(new Uri($uri));

            $xml = (new DataRdf($resourceObject))->transform();
            return $this->getSuccessResponse($xml, 202);
        } catch (Exception $e) {
            $code = $e->getCode();
            if ($code === 0 || $code=== null) {
                $code = 500;
            } 
            return $this->getErrorResponse($code, $e->getMessage());
        }
    }

    private function getResourceObjectFromRequestBody(ServerRequestInterface $request) {
        $doc = $this->getDomDocumentFromRequest($request);
        $descriptions = $doc->documentElement->getElementsByTagNameNs(Rdf::NAME_SPACE, 'Description');
        if ($descriptions->length != 1) {
            throw new ApiException(
            'Expected exactly one '
            . '/rdf:RDF/rdf:Description, got ' . $descriptions->length, 412
            );
        }
        
        $typeNode = $doc->createElement("rdf:type");
        $descriptions->item(0)->appendChild($typeNode);
        $typeNode ->setAttribute("rdf:resource", $this->manager->getResourceType());    
        $resources = (new Text($doc->saveXML()))->getResources();
        $resource = $resources[0];
        $className = Namespaces::mapRdfTypeToClassName($this->manager->getResourceType());
        if (!isset($resource) || !$resource instanceof $className) {
            throw new ApiException('XML Could not be converted to a ' . $className . ' object', 400);
        }

        return $resource;
    }

    private function getDomDocumentFromRequest(ServerRequestInterface $request) {
        $xml = $request->getBody();
        if (!$xml) {
            throw new ApiException('No RDF-XML recieved', 412);
        }

        $doc = new DOMDocument();
        if (!@$doc->loadXML($xml)) {
            throw new ApiException('Recieved RDF-XML is not valid XML', 412);
        }

        //do some basic tests
        if ($doc->documentElement->nodeName != 'rdf:RDF') {
            throw new ApiException(
            'Recieved RDF-XML is not valid: '
            . 'expected <rdf:RDF/> rootnode, got <' . $doc->documentElement->nodeName . '/>', 412
            );
        }
        return $doc;
    }

    protected function checkResourceIdentifiers($request, $resourceObject) {
        $params = $this->getAndAdaptQueryParams($request);

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
                throw new ApiException(
                'Parameter autoGenerateIdentifiers is set to true, but the provided '
                . 'xml already contains uri (rdf:about).', 400
                );
            };
            if (count($uuid)>0) {
                throw new ApiException(
                'Parameter autoGenerateIdentifiers is set to true, but the provided '
                . 'xml  already contains uuid.', 400
                );
            };
        } else {
            // Is uri missing
            if ($resourceObject->isBlankNode()) {
                throw new ApiException(
                'Uri (rdf:about) is missing from the xml. You may consider using autoGenerateIdentifiers.', 400
                );
            }
            if (count($uuid)===0) {
                throw new ApiException(
                'OpenSkos:uuid is missing from the xml. You may consider using autoGenerateIdentifiers.', 400
                );
            };
        }

        return $autoGenerateIdentifiers;
    }

    private function getParamValueFromParams($params, $paramname) {
        
        if (empty($params[$paramname])) {
            throw new ApiException('No ' . $paramname . ' specified', 412);
        }
        return $params[$paramname];
    }

    protected function getUserFromParams($params) {
        $key = $this->getParamValueFromParams($params, 'key');
        return $this->getUserByKey($key);
    }

   
   
    // override in concrete class when necessary
    protected function validate($resourceObject, $isForUpdate, $tenanturi) {
        $validator = new ResourceValidator($this->manager, $isForUpdate, $tenanturi);
        if (!$validator->validate($resourceObject)) {
            throw new ApiException(implode(' ' , $validator->getErrorMessages()), 400);
        } else {
            return true;
        }
    }
 
    
    
     /**
     * @params string $key
     * @return OpenSKOS_Db_Table_Row_User
     * @throws ApiException
     */
    protected function getUserByKey($key)
    {
        $user = OpenSKOS_Db_Table_Users::fetchByApiKey($key);
        if (null === $user) {
            throw new ApiException('No such API-key: `' . $key . '`', 401);
        }

        if (!$user->isApiAllowed()) {
            throw new ApiException('Your user account is not allowed to use the API', 401);
        }

        if (strtolower($user->active) !== 'y') {
            throw new ApiException('Your user account is blocked', 401);
        }
             
        return $user;
    }
    
     protected function getSuccessResponse($message, $status = 200, $format="text/xml") {
        $stream = new Stream('php://memory', 'wb+');
        $stream->write($message);
        $response = (new Response($stream, $status))
                ->withHeader('Content-Type', $format . ' ; charset="utf-8"');
        return $response;
    }

  
    
     /**
     * Get error response
     *
     * @param integer $status
     * @param string $message
     * @return ResponseInterface
     */
    protected function getErrorResponse($status, $message)
    {
        $stream = new Stream('php://memory', 'wb+');
        $stream->write($message);
        $response = (new Response($stream, $status, ['X-Error-Msg' => $message]));
        return $response;
    }
    
}
