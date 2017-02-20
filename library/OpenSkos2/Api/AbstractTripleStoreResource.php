<?php

namespace OpenSkos2\Api;

use DOMDocument;
use Exception;
use OpenSkos2\Preprocessor;
use OpenSkos2\Tenant;
use OpenSkos2\Api\Exception\ApiException;
use OpenSkos2\Api\Transform\DataRdf;
use OpenSkos2\Converter\Text;
use OpenSkos2\Namespaces;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Namespaces\Skos;
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

require_once dirname(__FILE__) . '/../config.inc.php';

abstract class AbstractTripleStoreResource {

  protected $manager;
  protected $authorisationManager;
  protected $deletionManager;

  public function getManager() {
    return $this->manager;
  }

  protected function getQueryParams(ServerRequestInterface $request) {
    $queryparams = $request->getQueryParams();
    if (empty($queryparams['key'])) {
      throw new ApiException('No user key specified', 412);
    }
    $user = $this->getUserByKey($queryparams['key']);
    $params = $queryparams;
    $params['user'] = $user;

    if ($this->manager->getResourceType() !== Tenant::TYPE) {
      if (!isset($queryparams['tenant']) || empty($queryparams['tenant'])) {
        throw new ApiException('No tenant specified', 412);
      }
      $params['tenantcode'] = $queryparams['tenant'];
      $tenantUri = $this->manager->fetchInstitutionUriByCode($params['tenant']);
      if ($tenantUri === null) {
        throw new ApiException('The tenant referred by code ' . $params['tenant'] . ' does not exist in the triple store. ', 400);
      }
      $params['tenantcode'] = $params['tenant'];
      $params['tenanturi'] = $tenantUri;
    } else {
      $params['tenantcode'] = 'undefined';
      $params['tenanturi'] = 'undefined';
    }
    return $params;
  }

  /* Returns a map, mapping resource's titles to the resource's Uri
   *  Works for set, schema, skos colllection, user relation definition
   */

  public function mapNameSearchID() {
    $index = $this->manager->fetchNameUri();
    return $index;
  }

  public function fetchDeatiledListResponse($params) {

    try {
      $index = $this->fetchDetailedList($params);

      // augmenting with tenants and sets when necessary 
      $rdfType = $this->manager->getResourceType();
      if ($rdfType === Skos::CONCEPT) {
        foreach ($index as $concept) {
          $spec = $this->manager->fecthTenantSpec($concept);
          foreach ($spec as $tenant_and_set) {
            $concept->addProperty(OpenSkos::SET, new \OpenSkos2\Rdf\Uri($tenant_and_set['seturi']));
            $concept->addProperty(OpenSkos::TENANT, new \OpenSkos2\Rdf\Uri($tenant_and_set['tenanturi']));
          }
        }
      } else {
        if ($rdfType === Skos::CONCEPTSCHEME || $rdfType === Skos::SKOSCOLLECTION) {
          foreach ($index as $resource) {
            $resource = $this->manager->augmentResourceWithTenant($resource);
          }
        }
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
      return $this->getErrorResponseFromException($e);
    }
  }

  private function fetchDetailedList($params) {
    $resType = $this->manager->getResourceType();
    if ($resType === Dcmi::DATASET && $params['allow_oai'] !== null) {
      $index = $this->manager->fetchAllSets($params['allow_oai']);
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
      return $this->getErrorResponseFromException($e);
    }
  }

  // Id is either an URI or uuid
  public function findResourceById($id) {
    $rdfType = $this->manager->getResourceType();
    $resource = $this->manager->findResourceById($id, $rdfType);
    if ($rdfType === Skos::CONCEPTSCHEME || $rdfType === Skos::SKOSCOLLECTION) {
      return $this->manager->augmentResourceWithTenant($resource);
    } else {
      if ($rdfType === Skos::CONCEPT) {
        $spec = $this->manager->fetchTenantSpec($resource);
        foreach ($spec as $tenant_and_set) {
          $resource->addProperty(OpenSkos::SET, new \OpenSkos2\Rdf\Uri($tenant_and_set['seturi']));
          $resource->addProperty(OpenSkos::TENANT, new \OpenSkos2\Rdf\Uri($tenant_and_set['tenanturi']));
        }
      };
      return $resource;
    }
  }

  public function create(ServerRequestInterface $request) {

    try {
      $params = $this->getQueryParams($request);
      $resourceObject = $this->getResourceObjectFromRequestBody($request);

      if (!$this->authorisationManager->resourceCreationAllowed($params['user'], $params['tenantcode'], $resourceObject)) {
        throw new ApiException('You do not have rights to create resource of type ' . $this->getManager()->getResourceType() . " in tenant " . $params['tenantcode'] . '. Your role is "' . $$params['user']->role . '"', 403);
      }
      if (!$resourceObject->isBlankNode() && $this->manager->askForUri((string) $resourceObject->getUri())) {
        throw new ApiException(
        'The resource with uri ' . $resourceObject->getUri() . ' already exists. Use PUT instead.', 400
        );
      }

      $autoGenerateUri = $this->checkResourceIdentifiers($request, $resourceObject);
      if ($this->manager->getResourceType() === Tenant::TYPE) {
        $preprocessor = new Preprocessor($this->manager, $this->manager->getResourceType(), null);
      } else {
        $preprocessor = new Preprocessor($this->manager, $this->manager->getResourceType(), $params['user']->getFoafPerson()->getUri());
      }
      $preprocessedResource = $preprocessor->forCreation($resourceObject, $params, $autoGenerateUri);
      $this->validate($preprocessedResource, false, $params['tenanturi']);
      $this->manager->insert($preprocessedResource);
      $savedResource = $this->manager->fetchByUri($preprocessedResource->getUri());
      $rdf = (new DataRdf($savedResource, true, []))->transform();
      return $this->getSuccessResponse($rdf, $preprocessedResource->getUri(), 201);
    } catch (Exception $e) {
      return $this->getErrorResponseFromException($e);
    }
  }

  public function update(ServerRequestInterface $request) {
    try {
      $resourceObject = $this->getResourceObjectFromRequestBody($request);
      if ($resourceObject->isBlankNode()) {
        throw new ApiException("Missed uri (rdf:about)!", 400);
      }
      $params = $this->getQueryParams($request);

      $uri = $resourceObject->getUri();
      if ($this->manager->getResourceType() === Tenant::TYPE) {
        $preprocessor = new Preprocessor($this->manager, $this->manager->getResourceType(), null);
      } else {
        $preprocessor = new Preprocessor($this->manager, $this->manager->getResourceType(), $params['user']->getFoafPerson()->getUri());
      }
      $preprocessedResource = $preprocessor->forUpdate($resourceObject, $params);
      if ($this->authorisationManager->resourceEditAllowed($params['user'], $params['tenantcode'], $preprocessedResource)) {
        $this->validate($preprocessedResource, true, $params['tenanturi']);
        $this->manager->replace($preprocessedResource);
        $savedResource = $this->manager->fetchByUri($resourceObject->getUri());
        $rdf = (new DataRdf($savedResource, true, []))->transform();
        return $this->getSuccessResponse($rdf, $resourceObject->getUri());
      } else {
        throw new ApiException('You do not have rights to edit resource ' . $uri . '. Your role is "' . $params['user']->role . '" in tenant ' . $params['tenantcode'], 403);
      }
    } catch (Exception $e) {
      return $this->getErrorResponseFromException($e);
    }
  }

  public function delete(ServerRequestInterface $request) {
    try {
      $params = $this->getQueryParams($request);
      if (empty($params['uri'])) {
        throw new ApiException('Missing uri parameter', 400);
      }

      $uri = $params['uri'];
      $resourceObject = $this->manager->fetchByUri($uri);
      if (!$resourceObject) {
        throw new ApiException('The resource is not found by uri :' . $uri, 404);
      }

      if (!$this->authorisationManager->resourceDeleteAllowed($params['user'], $params['tenantcode'], $resourceObject)) {
        throw new ApiException('You do not have rights to delete resource ' . $uri . '. Your role is "' . $params['user']->role . '" in tenant ' . $params['tenantcode'], 403);
      }

      $canBeDeleted = $this->deletionManager->canBeDeleted($uri, $this->manager);
      if (!$canBeDeleted) {
        throw new ApiException('The resource with the ' . $uri . ' cannot be deleted. Check if there are other resources referring to it. ', 412);
      }
      $this->manager->delete(new Uri($uri), $this->manager->getResourceType());
      $xml = (new DataRdf($resourceObject))->transform();
      return $this->getSuccessResponse($xml, $uri, 202);
    } catch (Exception $e) {
      return $this->getErrorResponseFromException($e);
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
    $typeNode->setAttribute("rdf:resource", $this->manager->getResourceType());
    $txt = (new Text($doc->saveXML()));
    $type = $this->manager->getResourceType();
    $resources = $txt->getResources($type);
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
    $params = $this->getQueryParams($request);
    $autoGenerateIdentifiers = false;
    if (!empty($params['autoGenerateIdentifiers'])) {
      $autoGenerateIdentifiers = filter_var(
        $params['autoGenerateIdentifiers'], FILTER_VALIDATE_BOOLEAN
      );
    }
    $uuid = $resourceObject->getProperty(OpenSkos::UUID);
    if ($autoGenerateIdentifiers) {
      if (!$resourceObject->isBlankNode()) {
        throw new ApiException(
        'Parameter autoGenerateIdentifiers is set to true, but the provided '
        . 'xml already contains uri (rdf:about).', 400
        );
      };
      if (count($uuid) > 0) {
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
      if (count($uuid) === 0) {
        throw new ApiException(
        'OpenSkos:uuid is missing from the xml. You may consider using autoGenerateIdentifiers.', 400
        );
      };
    }

    return $autoGenerateIdentifiers;
  }

  // override in the concrete class when necessary
  protected function validate($resourceObject, $isForUpdate, $tenanturi) {
    $validator = new ResourceValidator($this->manager, $isForUpdate, $tenanturi, true);
    if (!$validator->validate($resourceObject)) {
      throw new ApiException(implode(' ', $validator->getErrorMessages()), 400);
    } else {
      return true;
    }
  }

  /**
   * @params string $key
   * @return OpenSKOS_Db_Table_Row_User
   * @throws ApiException
   */
  protected function getUserByKey($key) {
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

  protected function getSuccessResponse($message, $uri, $status = 200, $format = "text/xml") {
    $stream = new Stream('php://memory', 'wb+');
    $stream->write($message);
    $response = (new Response($stream, $status, ['Location'=>$uri]))
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
  protected function getErrorResponse($status, $message) {
    $stream = new Stream('php://memory', 'wb+');
    $stream->write($message);
    $response = (new Response($stream, $status, ['X-Error-Msg' => $message]));
    return $response;
  }

  protected function getErrorResponseFromException($e) {
    $code = $e->getCode();
    if ($code === 0 || $code === null) {
      $code = 500;
    }
    $message = trim(preg_replace("/\r\n|\r|\n/", ' ', $e->getMessage()));
    return $this->getErrorResponse($code, $message);
  }

}
