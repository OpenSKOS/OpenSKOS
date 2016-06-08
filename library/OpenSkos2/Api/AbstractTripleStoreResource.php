<?php

namespace OpenSkos2\Api;

use DOMDocument;
use Exception;
use OpenSkos2\Relation;
use OpenSkos2\Api\Exception\ApiException;
use OpenSkos2\Api\Exception\NotFoundException;
use OpenSkos2\Api\Transform\DataRdf;
use OpenSkos2\Converter\Text;
use OpenSkos2\Namespaces;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Namespaces\Rdf;
use OpenSkos2\Namespaces\Dcmi;
use OpenSkos2\Namespaces\Org;
use OpenSkos2\Rdf\Literal;
use OpenSkos2\Rdf\Uri;
use OpenSkos2\Tenant as Tenant;
use OpenSkos2\Validator\Resource as ResourceValidator;
use OpenSKOS_Db_Table_Row_User;
use OpenSKOS_Db_Table_Users;
use Psr\Http\Message\ServerRequestInterface;
use Solarium\Exception\InvalidArgumentException;
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
    protected $tenant = array();
    
    public function getManager() {
        return $this->manager;
    }
    
   
    
    protected function getAndAdaptQueryParams(ServerRequestInterface $request) {
        $params = $request->getQueryParams();
        if (empty($params['tenant'])) {
            throw new InvalidArgumentException('No tenant specified', 412);
        };
        if (!CHECK_MYSQL) {
            $tenantUri = $this->manager->fetchInstitutionUriByCode($params['tenant']);
            if ($tenantUri === null) {
                throw new ApiException('The tenant referred by code ' . $params['tenant'] . ' does not exist in the triple store. You may want to set CHECK_MYSQL to true and allow search in the mysql database.', 400);
            }
        } else {
            $institution = $this->manager->fetchTenantFromMySqlByCode($params['tenant']);
            if ($institution === null) {
                throw new ApiException('The tenant referred by code ' . $params['tenant'] . ' is not found either in the triple store or in the mysql.', 400);
            };
            $tenantUri = null; // in the old setting institution must be is referred by code
        }
        $params['tenanturi'] = $tenantUri;
        $this->tenant['uri'] = $tenantUri;
        $this->tenant['code'] = $params['tenant'];
        return $params;
    }

    public function mapNameSearchID() {
        if (CHECK_MYSQL && ($this->manager->getResourceType() === Org::FORMALORG || $this->manager->getResourceType() === Dcmi::DATASET)) {
            $index = $this->manager->fetchNameCodeFromMySql();
        } else {
        $index =  $this->manager->fetchNameUri();
        }
        return $index;
    }

    
    public function fetchDeatiledListResponse($params) {
        $index = $this ->fetchDetailedList($params);
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
                    throw new InvalidArgumentException('Invalid context: ' . $params['context']);
        }
        return $response;
    }

    public function fetchDetailedList($params) {
        $resType = $this->manager->getResourceType();
        if (CHECK_MYSQL && (($resType === Dcmi::DATASET || $resType == Org::FORMALORG))) {
            $index = $this->manager->fetchFromMySQL($params);
            return $index;
        } 
        if ($resType === Dcmi::DATASET && $params['allow_oai']!== null) {
           $index =  $this->manager->fetch([OpenSkos::ALLOW_OAI => new \OpenSkos2\Rdf\Literal($params['allow_oai'], null, \OpenSkos2\Rdf\Literal::TYPE_BOOL),]); 
        } else {
            $index =  $this->manager->fetch();
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
                    throw new InvalidArgumentException('Invalid context: ' . $context);
            }

            return $response;
        } catch (Exception $ex) {
            return $this->getErrorResponse(500, $ex->getMessage());
        }
    }

    // Id is either an URI or uuid
    public function findResourceById($id) {
        if ($id !== null && isset($id)) {
            if (substr($id, 0, 7) === "http://" || substr($id, 0, 8) === "https://") {
                $resource = $this->manager->fetchByUri($id);
                return $resource;
            }

            if (CHECK_MYSQL && $this->manager->getResourceType() === Org::FORMALORG) {
                $mysqlres = $this->manager->fetchTenantFromMySqlByCode($id);
                $resource = $this->manager->translateTenantMySqlToRdf($mysqlres);
                return $resource;
            };
            if (CHECK_MYSQL && $this->manager->getResourceType() === Dcmi::DATASET) {
                $mysqlres = $this->manager->fetchSetFromMySqlByCode($id);
                $resource = $this->manager->translateMySqlCollectionToRdfSet($mysqlres);
                return $resource;
            };
            $resource = $this->manager->fetchByUuid($id);
            return $resource;
        } else {
            throw new InvalidArgumentException('No Id (URI or UUID, or Code for older settings) is given');
        }
    }

    public function create(ServerRequestInterface $request) {
       
        try {
            $params = $this->getAndAdaptQueryParams($request);
            $user = $this->getUserFromParams($params);
            $resourceObject = $this->getResourceObjectFromRequestBody($request);
            
            if (!$this->authorisationManager->resourceCreationAllowed($user, $this->tenant['code'], $this->tenant['uri'], $resourceObject)) {
                throw new ApiException('You do not have rights to create resource of type '. $this->getManager()->getResourceType() . " in tenant " . $this->tenant['code'] . '. Your role is "' .  $user->role . '"', 403); 
            }
            if (!$resourceObject->isBlankNode() && $this->manager->askForUri((string) $resourceObject->getUri())) {
                throw new InvalidArgumentException(
                'The resource with uri ' . $resourceObject->getUri() . ' already exists. Use PUT instead.', 400
                );
            }

            $autoGenerateUri = $this->checkResourceIdentifiers($request, $resourceObject);
            $resourceObject->addMetadata($user, $params, array());
            if ($autoGenerateUri) {
                $resourceObject -> selfGenerateUri($this->tenant['code'], $this->manager);
            };
            $this->validate($resourceObject, $this->tenant);
            $this->manager->insert($resourceObject);
            $savedResource = $this->manager->fetchByUri($resourceObject->getUri());
            $rdf = (new DataRdf($savedResource, true, []))->transform();
            return $this->getSuccessResponse($rdf, 201);
        } catch (Exception $e) {
            return $this->getErrorResponse($e->getCode(), $e->getMessage());
        }
    }
    
    public function update(ServerRequestInterface $request) {
        try {
            $resourceObject = $this->getResourceObjectFromRequestBody($request);
            if ($resourceObject->isBlankNode()) {
                throw new ApiException("Missed uri (rdf:about)!", 400);
            }

            $uri = $resourceObject->getUri();
            $existingResource = $this->manager->fetchByUri((string) $uri);
            $params = $this->getAndAdaptQueryParams($request);
            $user = $this->getUserFromParams($params);
            $oldUuid = $existingResource->getUuid();
            if ($oldUuid instanceof Literal) {
                $oldUuid = $oldUuid->getValue();
            }
            $oldParams = [
                'uuid' => $oldUuid,
                'creator' => $existingResource->getCreator(),
                'dateSubmitted' => $existingResource->getDateSubmitted(),
                'status' => $existingResource->getStatus() // so fat, not null only for concepts
            ];

            if ($this->manager->getResourceType() !== Relation::TYPE) { // we do not have an uuid for relations
                // do not update uuid: it must be intact forever, connected to uri
                $uuid = $resourceObject->getUuid();
                if ($uuid instanceof Literal) {
                    $uuid = $uuid->getValue();
                }
                if ($uuid !== false && $uuid !== null) {
                    if ($uuid !== $oldParams['uuid']) {
                        throw new ApiException('You cannot change UUID of the resouce. Keep it ' . $oldParams['uuid'], 400);
                    }
                }
            }


            $resourceObject->addMetadata($user, $params, $oldParams);
            if ($this->authorisationManager->resourceEditAllowed($user, $this->tenant['code'], $this->tenant['uri'], $existingResource)) {
                $this->validateForUpdate($resourceObject, $this->tenant, $existingResource);
                $this->manager->replace($resourceObject);
                $savedResource = $this->manager->fetchByUri($resourceObject->getUri());
                $rdf = (new DataRdf($savedResource, true, []))->transform();
                return $this->getSuccessResponse($rdf, 201);
            } else {
                throw new ApiException('You do not have rights to edit resource ' . $uri  . '. Your role is "' . $user->role . '" in tenant ' . $this->tenant['code'], 403);
            }
        } catch (Exception $e) {
            return $this->getErrorResponse($e->getCode(), $e->getMessage());
        }
    }

    public function deleteResourceObject(ServerRequestInterface $request) {
        try {
            $params = $this->getAndAdaptQueryParams($request);
            if (empty($params['uri'])) {
                throw new InvalidArgumentException('Missing uri parameter');
            }

            $uri = $params['uri'];
            $resourceObject = $this->manager->fetchByUri($uri);
            if (!$resourceObject) {
                throw new NotFoundException('The resource is not found by uri :' . $uri, 404);
            }

            $user = $this->getUserFromParams($params);
            if (!$this->authorisationManager->resourceDeleteAllowed($user, $this->tenant['code'], $this->tenant['uri'], $resourceObject)) {
                 throw new ApiException('You do not have rights to delete resource ' . $uri  . '. Your role is "' . $user->role . '" in tenant ' . $this->tenant['code'], 403);
            }

            $canBeDeleted = $this->deletionManager->canBeDeleted($uri, $this->manager);
            if (!$canBeDeleted) {
                throw new ApiException('The resource with the ' . $uri . ' cannot be deleted. Check if there are other resources referring to it. ', 412);
            }
            $this->manager->delete(new Uri($uri));

            $xml = (new DataRdf($resourceObject))->transform();
            return $this->getSuccessResponse($xml, 202);
        } catch (ApiException $ex) {
            return $this->getErrorResponse($ex->getCode(), $ex->getMessage());
        }
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
        
        $typeNode = $doc->createElement("rdf:type");
        $descriptions->item(0)->appendChild($typeNode);
        $typeNode ->setAttribute("rdf:resource", $this->manager->getResourceType());    
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

    protected function getUserFromParams($params) {
        $key = $this->getParamValueFromParams($params, 'key');
        return $this->getUserByKey($key);
    }

   
   
    // override in concrete class when necessary
    protected function validate($resourceObject, Array $tenant) {
        $validator = new ResourceValidator($this->manager, new Tenant($tenant['code']));
        if (!$validator->validate($resourceObject)) {
            throw new InvalidArgumentException(implode(' ', $validator->getErrorMessages()), 400);
        }
        //content validation, where you need to look up the triple store
        $uuid = $resourceObject -> getProperty(OpenSkos::UUID);
        $resType = $this->manager -> getResourceType();
        $resources= $this -> manager -> fetchSubjectWithPropertyGiven(OpenSkos::UUID, '"'.$uuid[0].'"' , $resType);
        if (count($resources)>0) {
           throw new ApiException('The resource with the uuid ' . $uuid[0] . ' has been already registered.', 400);
       }
    }
    
     // override in concrete class when necessary
    protected function validateForUpdate($resourceObject, Array $tenant, $existingResourceObject) {
        $validator = new ResourceValidator($this->manager, new Tenant($tenant['code']));
        if (!$validator->validate($resourceObject)) {
            throw new InvalidArgumentException(implode(' ', $validator->getErrorMessages()), 400);
        }
       
    }
    
    // property-content validations, where you need to look up the triple store
    
    // a new property must be new (they are single valued ones like: uri, uuid, label, title, notation) 
    protected function validatePropertyForCreate($resourceObject, $propertyUri, $rdfType) {
        $vals = $resourceObject->getProperty($propertyUri);
        foreach ($vals as $val) {
            $atlang = $this->retrieveLanguagePrefix($val);
            $resources = $this->manager->fetchSubjectWithPropertyGiven($propertyUri, '"' . trim($val) . '"' . $atlang, $rdfType);
            if (count($resources) > 0) {
                throw new ApiException('The resource ' . $this->manager->getResourceType() . '  with the property ' . $propertyUri . ' of value ' . $val . $atlang . ' has been already registered.', 400);
            }
        }
    }

    // a new property must be new (they are single valued ones like: uri, uuid, label, title, notation) 
    protected function validatePropertyForUpdate($resourceObject, $existingResourceObject, $propertyUri, $rdfType) {
        $values = $resourceObject->getProperty($propertyUri);
        $oldValues = $existingResourceObject->getProperty($propertyUri);
        foreach ($values as $value) {
            $lan = $this->retrieveLanguagePrefix($value);
            foreach ($oldValues as $oldValue) {
                $oldLan = $this->retrieveLanguagePrefix($oldValue);
                if ($value->getValue() !== $oldValue->getValue() || $lan !== $oldLan) { //  new title is given
                    // new val should not occur amnogst existing old values of the same property
                    $resources = $this->manager->fetchSubjectWithPropertyGiven($propertyUri, '"' . $value . '"' . $lan, $rdfType);
                    if (count($resources) > 0) {
                        throw new ApiException('The resource ' . $this->manager->getResourceType() . '  with the property ' . $propertyUri . ' of value ' . $value . $lan . ' has been already registered.', 400);
                    }
                }
            }
        }
    }

    // the resource referred by the uri must exist in the triple store, 
    // or for backward compatibility of tenants and sets one must check their code as well
    // if CHECK_MYSQL is set to true (see /../config.inc.php)
    protected function validateURI($resourceObject, $property, $rdfType) {
        $val = $resourceObject->getProperty($property);
        if ($val === null) {
            return true;
        }
        if (count($val)===0) {
            return true;
        }
        foreach ($val as $uri) {
            if (CHECK_MYSQL) {
                if ($rdfType === Org::FORMALORG) { // check in the mysql, 
                    $institution = $this->manager->fetchTenantFromMySqlByCode($uri);
                    if ($institution === null) {
                        throw new ApiException('The tenant referred by code/uri ' . $uri . ' is not found either in the triple store or in the mysql.', 400);
                    }
                };
                if ($rdfType === Dcmi::DATASET) { // check in the mysql
                    $set = $this->manager->fetchSetFromMySqlByCode($uri);
                    if ($set === null) {
                        throw new ApiException('The set referred by code/uri ' . $uri . ' is not found either in the triple store or in the mysql.', 400);
                    }
                };
            } else {
                $count = $this->manager->countTriples('<' . trim($uri) . '>', '<' . Rdf::TYPE . '>', '<' . $rdfType . '>');
                if ($count < 1) {
                    throw new ApiException('The resource referred by  uri ' . $uri . ' is not found in the triple store. ', 400);
                }
            }
        }
    }

    private function retrieveLanguagePrefix($val){
        if ($val instanceof Literal) {
            $lang = $val->getLanguage();
            if ($lang !== null && $lang !== "") {
               return "@" . $lang;
            }
        }
        return "";
    }

     /**
     * @params string $key
     * @return OpenSKOS_Db_Table_Row_User
     * @throws InvalidArgumentException
     */
    protected function getUserByKey($key)
    {
        $user = OpenSKOS_Db_Table_Users::fetchByApiKey($key);
        if (null === $user) {
            throw new InvalidArgumentException('No such API-key: `' . $key . '`', 401);
        }

        if (!$user->isApiAllowed()) {
            throw new InvalidArgumentException('Your user account is not allowed to use the API', 401);
        }

        if (strtolower($user->active) !== 'y') {
            throw new InvalidArgumentException('Your user account is blocked', 401);
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
