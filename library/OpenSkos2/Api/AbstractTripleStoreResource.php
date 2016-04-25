<?php

namespace OpenSkos2\Api;

use DOMDocument;
use Exception;
use OpenSkos2\Api\Exception\ApiException;
use OpenSkos2\Api\Exception\NotFoundException;
use OpenSkos2\Api\Transform\DataArray;
use OpenSkos2\Api\Transform\DataRdf;
use OpenSkos2\Converter\Text;
use OpenSkos2\Namespaces;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Namespaces\Rdf;
use OpenSkos2\Rdf\Literal;
use OpenSkos2\Rdf\Uri;
use OpenSkos2\Tenant as Tenant;
use OpenSkos2\Validator\Resource as ResourceValidator;
use OpenSKOS_Db_Table_Row_User;
use Psr\Http\Message\ServerRequestInterface;
use Solarium\Exception\InvalidArgumentException;
use Zend\Diactoros\Response;
use Zend\Diactoros\Stream;

require_once dirname(__FILE__) .'/../config.inc.php';

abstract class AbstractTripleStoreResource {

    use \OpenSkos2\Api\Response\ApiResponseTrait;

    protected $manager;
    
    protected $tenant = array();
    
    public function getManager() {
        return $this->manager;
    }
    
    
    protected function getAndAdaptQueryParams(ServerRequestInterface $request){
        $params = $request->getQueryParams();
        if (empty($params['tenant'])) {
            throw new InvalidArgumentException('No tenant specified', 412);
        }
        $tenantUri = $this -> manager -> fetchInstitutionUriByCode($params['tenant']);
        $params['tenanturi'] = $tenantUri;
        // side effect: setting up a current-requester tenant vor different sort checks and validations
        $this->tenant['code'] = $params['tenant'];
        $this->tenant['uri'] = $tenantUri;
        return $params;
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
                    $prep = (new DataArray($resource, []))->transform();
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
            $params = $this->getAndAdaptQueryParams($request);
            $user = $this->getUserFromParams($params);
            if (!$this->resourceCreationAllowed($user)) {
                throw new ApiException('You do not have rights to create resource of type '. $this->getManager()->getResourceType(), 403); 
            }
            $resourceObject = $this->getResourceObjectFromRequestBody($request);
            if (!$resourceObject->isBlankNode() && $this->manager->askForUri((string) $resourceObject->getUri())) {
                throw new InvalidArgumentException(
                'The resource with uri ' . $resourceObject->getUri() . ' already exists. Use PUT instead.', 400
                );
            }

            $autoGenerateUri = $this->checkResourceIdentifiers($request, $resourceObject);
            $resourceObject->addMetadata($user, $params, array());
            if ($autoGenerateUri) {
                $resourceObject->selfGenerateUri($this->tenant['code'], $this->manager);
            };
            $this->validate($resourceObject, $this->tenant);
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
            if ($resourceObject->isBlankNode()) {
                throw new Exception("Missed uri (rdf:about)!");
            }
            $uri = $resourceObject->getUri();
            $existingResource = $this->manager->fetchByUri((string)$uri);
            $params = $this->getAndAdaptQueryParams($request);
            $user = $this->getUserFromParams($params);
            
             
            // do not update uuid: it must be intact forever, connected to uri
            $uuid = $resourceObject->getProperty(OpenSkos::UUID);
            if (count($uuid)>0) {
                $oldUuid = $existingResource->getProperty(OpenSkos::UUID);
                if ($uuid[0]->getValue() !== $oldUuid[0]->getValue()) {
                    throw new ApiException('You cannot change UUID of the resouce. Keep it ' . $oldUuid[0], 400);
                }
            }
            
            $oldParams = [
                'uuid' => $existingResource -> getUUID(),
                'creator' => $existingResource -> getCreator(),
                'dateSubmitted' => $existingResource -> getDateSubmitted(),
                'status' => $existingResource -> getStatus() // so fat, not null only for concepts
            ];
            
            $resourceObject->addMetadata($user, $params, $oldParams);
            
            $this->resourceEditAllowed($user, $this->tenant, $existingResource);    

            $this->validateForUpdate($resourceObject, $this->tenant, $existingResource);
           
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
            if (!$this->resourceDeleteAllowed($user)) {
                throw new ApiException('You do not have rights to delete this resource ' . $uri, 403);
            }

            $canBeDeleted = $this->manager->CanBeDeleted($uri);
            if (!$canBeDeleted) {
                throw new ApiException('The resource with the ' . $uri . ' cannot be deleted. Check if there are other resources referring to it. ', 412);
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

    private function checkResourceIdentifiers(ServerRequestInterface $request, $resourceObject) {
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

    protected function getSuccessResponse($message, $status = 200, $format="text/xml") {
        $stream = new Stream('php://memory', 'wb+');
        $stream->write($message);
        $response = (new Response($stream, $status))
                ->withHeader('Content-Type', $format . ' ; charset="utf-8"');
        return $response;
    }

    // override in concrete rclass when necessary
    protected function resourceDeleteAllowed(OpenSKOS_Db_Table_Row_User $user) {
        return  ($user->role === ADMINISRATOR || $user->role === ROOT);
    }
    
    // in the checking and validation functions below
    // $tenant is a map $tenant['code'], $tenant['uri']
    
    // override in concrete class when necessary
   
    protected function resourceEditAllowed(OpenSKOS_Db_Table_Row_User $user, Array $tenant=null, $resource=null) {
        return ($user->role === ADMINISRATOR || $user->role === ROOT);
    }
    
     // override in concrete class when necessary
    protected function resourceCreationAllowed(OpenSKOS_Db_Table_Row_User $user, Array $tenant=null, $resource=null) {
        return ($user->role === ADMINISRATOR || $user->role === ROOT);
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
        $val=$vals[0];
        $atlang = $this ->retrieveLanguagePrefix($val);
        $resources = $this->manager->fetchSubjectWithPropertyGiven($propertyUri, '"' . trim($val) . '"'.$atlang, $rdfType);
        if (count($resources) > 0) {
            throw new ApiException('The resource ' . $this->manager->getResourceType() .'  with the property '. $propertyUri  .' of value '. $val.$atlang . ' has been already registered.', 400);
        }
    }
    
    // the resource referred by the uri must exist
    protected function validateURI($resourceObject, $uri, $rdfType) {
        $val = $resourceObject->getProperty($uri);
        foreach ($val as $uri) {
            $count = $this->manager->countTriples('<' . trim($uri) . '>', '<' . Rdf::TYPE . '>', '<' . $rdfType . '>');
            if ($count < 1) {
                throw new ApiException('The sub-resource referred by the uri ' . $uri . ' of value ' . $val[0] . ' does not exist.', 400);
            }
        }
    }

   // a new property must be new (they are single valued ones like: uri, uuid, label, title, notation) 
    protected function validatePropertyForUpdate($resourceObject, $existingResourceObject, $propertyUri, $rdfType) {
        $values= $resourceObject->getProperty($propertyUri);
        $value = $values[0];
        $lan = $this ->retrieveLanguagePrefix($value);
        $oldValues = $existingResourceObject ->getProperty($propertyUri);
        $oldValue = $oldValues[0];
        $oldLan = $this ->retrieveLanguagePrefix($oldValue);
        if ($value->getValue() !== $oldValue->getValue() || $lan !== $oldLan) { //  new title is given
            // new val should not occur amnogst existing old values of the same property
            $resources = $this->manager->fetchSubjectWithPropertyGiven($propertyUri, '"'.$value.'"'.$lan, $rdfType);
            if (count($resources) > 0) {
                throw new ApiException('The resource ' . $this->manager->getResourceType() .'  with the property '. $propertyUri  .' of value '. $value.$lan . ' has been already registered.', 400);
            }
        } 
   }
    
   public function fetchUriName(){
       return $this ->manager-> fetchUriName();
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

}
