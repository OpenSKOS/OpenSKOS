<?php

namespace OpenSkos2\Api;

use DOMDocument;
use Exception;
use OpenSkos2\Api\Exception\ApiException;
use OpenSkos2\Api\Exception\InvalidPredicateException;
use OpenSkos2\Api\Exception\NotFoundException;
use OpenSkos2\Api\Response\Detail\JsonpResponse as DetailJsonpResponse;
use OpenSkos2\Api\Response\Detail\JsonResponse as DetailJsonResponse;
use OpenSkos2\Api\Response\Detail\RdfResponse as DetailRdfResponse;
use OpenSkos2\Api\Response\ResultSet\JsonResponse;
use OpenSkos2\Api\Response\ResultSet\JsonpResponse;
use OpenSkos2\Api\Response\ResultSet\RdfResponse;
use OpenSkos2\Api\Transform\DataRdf;
use OpenSkos2\Concept;
use OpenSkos2\ConceptScheme;
use OpenSkos2\FieldsMaps;
use OpenSkos2\Rdf\Uri;
use OpenSkos2\RelationType;
use OpenSkos2\Set;
use OpenSkos2\SkosCollection;
use OpenSkos2\Tenant;
use OpenSkos2\Relation;
use OpenSkos2\Converter\Text;
use OpenSkos2\Exception\ResourceNotFoundException;
use OpenSkos2\Namespaces;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Namespaces\Rdf;
use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Namespaces\Dc;
use OpenSkos2\Validator\Resource as ResourceValidator;
use OpenSKOS_Db_Table_Row_User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Solarium\Exception\InvalidArgumentException;
use Zend\Diactoros\Response;
use Zend\Diactoros\Stream;

abstract class AbstractTripleStoreResource
{

    use \OpenSkos2\Api\Response\ApiResponseTrait;

    /**
     * Person manager
     *
     * @var PersonManager
     */
    protected $personManager;

    /*
     * 
     * @var TenantManager|SetManager|ConceptSchemeManager|SkosCollectionManager|ConceptManager|RelationTypeManager|RelationManager
     */
    protected $manager;

    /**
     * Authorisation rules
     * 
     * @var Authorisation
     */
    protected $authorisation;

    /**
     * Deletion rules
     * 
     * @var Deletion
     */
    protected $deletion;

    /**
     * array of application.ini settings
     * 
     * @var init
     */
    protected $init;

    /**
     * Get PSR-7 response for resource
     *
     * @param $request \Psr\Http\Message\ServerRequestInterface
     * @param string $context
     * @throws NotFoundException
     * @throws InvalidArgumentException
     * @return ResponseInterface
     */
    public function getResourceResponse(ServerRequestInterface $request, $id, $context)
    {
        $resource = $this->getResource($id);

        $params = $request->getQueryParams();

        if (isset($params['fl'])) {
            $propertiesList = $this->fieldsListToProperties($params['fl']);
        } else {
            $propertiesList = [];
        }

        if (($context === 'json' || $context === 'jsonp') && $this->manager->getResourceType() === Tenant::TYPE) {
            $fieldname = 'sets';
            $extrasGraph = $this->manager->fetchSetsForTenantUri($resource->getUri());
            $extras = \OpenSkos2\Bridge\EasyRdf::graphToResourceCollection($extrasGraph);
        } else {
            $fieldname = null;
            $extras = [];
        }


        switch ($context) {
            case 'json':
                $response = (new DetailJsonResponse($resource, $propertiesList, $fieldname, $extras))->getResponse();
                break;
            case 'jsonp':
                $response = (new DetailJsonpResponse($resource, $params['callback'], $propertiesList, $fieldname, $extras))->getResponse();
                break;
            case 'rdf':
                $response = (new DetailRdfResponse($resource, $propertiesList))->getResponse();
                break;
            default:
                throw new InvalidArgumentException('Invalid context: ' . $context);
        }
        return $response;
    }

    /**
     * Get openskos resource
     *
     * @param string|Uri $id
     * @throws NotFoundException
     * @throws Exception\DeletedException
     * @return a sublcass of \OpenSkos2\Resource
     */
    public function getResource($id)
    {
        if ($id instanceof Uri) {
            $resource = $this->manager->fetchByUri($id);
        } else {
            try {
                $resource = $this->manager->fetchByUuid($id);
            } catch (ResourceNotFoundException $ex) {
                $rdfType = $this->manager->getResourceType();
                if ($rdfType === Set::TYPE || $rdfType === Tenant::TYPE) {
                    $resource = $this->manager->fetchByCode($id, $rdfType);
                } else {
                    throw $ex;
                }
            }
        }

        if ($resource->isDeleted()) {
            throw new NotFoundException('Resource ' . $id . ' is deleted', 410);
        }

        // augmenting concept with set, tenant and dc:creator
        if ($this->manager->getResourceType() === Concept::TYPE) {
            $specs = $this->manager->fetchConceptSpec($resource);
            foreach ($specs as $spec) {
                $resource->addProperty(OpenSkos::SET, new \OpenSkos2\Rdf\Literal($spec['setcode']));
                $resource->addProperty(OpenSkos::TENANT, new \OpenSkos2\Rdf\Literal($spec['tenantcode']));
                $resource->addProperty(Dc::CREATOR, new \OpenSkos2\Rdf\Literal($spec['creatorname']));
            }
        }

        return $resource;
    }

    /**
     * Create the concept
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function create(ServerRequestInterface $request)
    {
        try {
            $response = $this->handleCreate($request);
        } catch (ApiException $ex) {
            return $this->getErrorResponse($ex->getCode(), $ex->getMessage());
        }
        return $response;
    }

    /**
     * Update a resource
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function update(ServerRequestInterface $request)
    {
        try {
            $params = $this->getParams($request);

            $tenantAndSet = $this->getTenantAndSetFromParams($params);

            $resource = $this->getResourceFromRequest($request, $tenantAndSet['tenant']);

            $existingResource = $this->manager->fetchByUri((string) $resource->getUri());

            $user = $this->getUserFromParams($params);

            $this->authorisation->resourceEditAllowed($user, $tenantAndSet['tenant'], $tenantAndSet['set'], $resource);
            $resource->ensureMetadata(
                $tenantAndSet['tenantUri'], $tenantAndSet['setUri'], $user->getFoafPerson(), $this->personManager, $existingResource);

            $this->validate($resource, $tenantAndSet['tenant'], $tenantAndSet['set'], true);

            if ($this->manager->getResourceType() === Concept::TYPE) {
                $this->manager->replaceAndCleanRelations($resource);
            }
        } catch (ApiException $ex) {
            return $this->getErrorResponse($ex->getCode(), $ex->getMessage());
        } catch (ResourceNotFoundException $ex) {
            return $this->getErrorResponse(404, $ex->getMessage() . "Try POST. ");
        }

        $xml = (new DataRdf($resource))->transform();
        return $this->getSuccessResponse($xml);
    }

    /**
     * Perform a soft delete on a concept
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws InvalidArgumentException
     * @throws NotFoundException
     */
    public function delete(ServerRequestInterface $request)
    {
        try {

            $params = $request->getQueryParams();
            if (empty($params['id'])) {
                throw new InvalidArgumentException('Missing id parameter');
            }

            $id = $params['id'];
            $rdfType = $this->manager->getResourceType();
            $resource = $this->manager->fetchByUri($id, $rdfType);
            if (!$resource) {
                throw new NotFoundException('Resource not found by id :' . $id, 404);
            }


            if ($rdfType === Concept::TYPE) {
                if ($resource->isDeleted()) {
                    throw new NotFoundException('Resource already deleted :' . $id, 410);
                }
            }

            $user = $this->getUserFromParams($params);
            $tenantAndSet = $this->getTenantAndSetFromParams($params);

            $this->authorisation->resourceDeleteAllowed($user, $tenantAndSet['tenant'], $tenantAndSet['set'], $resource);
            $this->deletion->canBeDeleted($id); // deletion is blocked for non-concept resources if nthere are references to this resource; deletion for concepts is not blocked; can be customized

            if ($rdfType === Concept::TYPE) {
                $this->manager->deleteSoft($resource);
            } else {
                $this->manager->delete($resource);
            }
        } catch (ApiException $ex) {
            return $this->getErrorResponse($ex->getCode(), $ex->getMessage());
        }
        $xml = (new DataRdf($resource))->transform();
        return $this->getSuccessResponse($xml, 202);
    }

    /**
     * Gets a list (array or string) of fields and try to recognise the properties from it.
     * @param array $fieldsList
     * @return array
     * @throws InvalidPredicateException
     */
    protected function fieldsListToProperties($fieldsList)
    {
        if (!is_array($fieldsList)) {
            $fieldsList = array_map('trim', explode(',', $fieldsList));
        }
        $propertiesList = [];
        $fieldsMap = FieldsMaps::getNamesToProperties();
        // Tries to search for the field in fields map.
        // If not found there tries to expand it from short property.
        // If not that - just pass it as it is.
        foreach ($fieldsList as $field) {
            if (!empty($field)) {
                if (isset($fieldsMap[$field])) {
                    $propertiesList[] = $fieldsMap[$field];
                } else {
                    $propertiesList[] = Namespaces::expandProperty($field);
                }
            }
        }
        // Check if we have a nice properties list at the end.
        foreach ($propertiesList as $propertyUri) {
            if ($propertyUri == 'uri') {
                continue;
            }
            if (filter_var($propertyUri, FILTER_VALIDATE_URL) == false) {
                throw new InvalidPredicateException(
                'The field "' . $propertyUri . '" from fields list is not recognised.'
                );
            }
        }
        return $propertiesList;
    }

    /**
     * Applies all validators to the concept.
     * @param \OpenSkos2\Resource $resource
     * @param Tenant|null $tenant   
     * @param Set|null $set
     * @param bool $isForUpdate
     * @throws InvalidArgumentException
     */
    protected function validate($resource, $tenant, $set, $isForUpdate)
    {
        // the last parameter switches check if the referred within the resource objects do exists in the triple store
        $validator = new ResourceValidator(
            $this->manager, $tenant, $set, $isForUpdate, true
        );
        if (!$validator->validate($resource)) {
            throw new InvalidArgumentException(implode(' ', $validator->getErrorMessages()), 400);
        }
    }

    /**
     * Handle the action of creating the concept
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws InvalidArgumentException
     */
    private function handleCreate(ServerRequestInterface $request)
    {
        $params = $this->getParams($request);

        $tenantAndSet = $this->getTenantAndSetFromParams($params);

        $resource = $this->getResourceFromRequest($request, $tenantAndSet['tenant']);

        if (!$resource->isBlankNode() && $this->manager->askForUri((string) $resource->getUri())) {
            throw new InvalidArgumentException(
            'The resource with uri ' . $resource->getUri() . ' already exists. Use PUT instead.', 400
            );
        }

        $user = $this->getUserFromParams($params);

        $this->authorisation->resourceCreateAllowed($user, $tenantAndSet['tenant'], $tenantAndSet['set'], $resource);

        $resource->ensureMetadata(
            $tenantAndSet['tenantUri'], $tenantAndSet['setUri'], $user->getFoafPerson(), $this->personManager
        );

        $autoGenerateUri = $this->checkResourceIdentifiers($request, $resource);

        if ($autoGenerateUri) {
            $resource->selfGenerateUri(
                $tenantAndSet['tenant'], $tenantAndSet['set'], $this->manager
            );
        }


        $this->validate(
            $resource, $tenantAndSet['tenant'], $tenantAndSet['set'], false);


        $this->manager->insert($resource);

        $rdf = (new DataRdf($resource))->transform();

        return $this->getSuccessResponse($rdf, 201);
    }

    /**
     * Get request params, including parameters send in XML body
     *
     * @param ServerRequestInterface $request
     * @return array
     */
    private function getParams(ServerRequestInterface $request)
    {
        $params = $request->getQueryParams();
        $doc = $this->getDomDocumentFromRequest($request);

        // is a tenant, collection or api key set in the XML?
        if ($this->init['custom.backward_compatible']) {
            $set = 'collection';
        } else {
            $set = 'set';
        }

        $rdfType = $this->manager->getResourceType();

        switch ($rdfType) {
            case Tenant::TYPE:
                $required = array('key');
                break;
            case Set::TYPE:
                $required = array('tenant', 'key');
                break;
            case ConceptScheme::TYPE:
                $required = array('tenant', $set, 'key');
                break;
            case SkosCollection::TYPE:
                $required = array('tenant', $set, 'key');
                break;
            case Concept::TYPE:
                $required = array('tenant', $set, 'key');
                break;
            case RelationType::TYPE:
                $required = array('key');
                break;
            case Relation::TYPE:
                $required = array('key');
                break;
        }


        foreach ($required as $attributeName) {
            $value = $doc->documentElement->getAttributeNS(OpenSkos::NAME_SPACE, $attributeName);
            if (!empty($value)) {
                $params[$attributeName] = $value;
            }
        }
        return $params;
    }

    /**
     * Get the skos concept from the request to insert or update
     * does some validation to see if the xml is valid
     *
     * @param ServerRequestInterface $request
     * @param Tenant $tenant
     * @return \OpenSkos2\*
     * @throws InvalidArgumentException
     */
    private function getResourceFromRequest(ServerRequestInterface $request, $tenant)
    {
        $doc = $this->getDomDocumentFromRequest($request);

        $descriptions = $doc->documentElement->getElementsByTagNameNS(Rdf::NAME_SPACE, 'Description');

        if ($descriptions->length != 1) {
            throw new InvalidArgumentException(
            'Expected exactly one '
            . '/rdf:RDF/rdf:Description, got ' . $descriptions->length, 412
            );
        }
        // remove the api key
        $doc->documentElement->removeAttributeNS(OpenSkos::NAME_SPACE, 'key');

        $rdfType = $this->manager->getResourceType();
        $resources = (new Text($doc->saveXML()))->getResources($rdfType);
        $resource = $resources[0];

        $className = Namespaces::mapRdfTypeToClassName($rdfType);
        if (!isset($resource) || !$resource instanceof $className) {
            throw new InvalidArgumentException('XML Could not be converted to ' . $rdfType, 400);
        }
        // Is a tenant in the custom openskos xml attributes but not in the rdf add the values to the resource
        // Meertens tenant is not added to rdf's of concept, schema or skos collection, because it is derivable from the resources containing tthe resource (for schema and skos collection sit is derivabek via their sets, for concepts it is derivable via its concept scheme).
        if ($rdfType === Set::TYPE) {
            $resource = $this->setTenantForSet($tenant, $resource, $doc);
        }
        return $resource;
    }

    /**
     * Get dom document from request
     *
     * @param ServerRequestInterface $request
     * @return DOMDocument
     * @throws InvalidArgumentException
     */
    private function getDomDocumentFromRequest(ServerRequestInterface $request)
    {
        $xml = $request->getBody();
        if (!$xml) {
            throw new InvalidArgumentException('No RDF-XML recieved', 400);
        }
        $doc = new DOMDocument();
        if (!@$doc->loadXML($xml)) {
            throw new InvalidArgumentException('Recieved RDF-XML is not valid XML', 400);
        }
        //do some basic tests
        if ($doc->documentElement->nodeName != 'rdf:RDF') {
            throw new InvalidArgumentException(
            'Recieved RDF-XML is not valid: '
            . 'expected <rdf:RDF/> rootnode, got <' . $doc->documentElement->nodeName . '/>', 400
            );
        }
        return $doc;
    }

    /**
     * @param $params
     * @param Tenant $tenant
     * @return Set
     * @throws InvalidArgumentException
     */
    private function getSet($params, $tenant)
    {
        $rdfType = $this->manager->getResourceType();
        if ($rdfType === Concept::TYPE || $rdfType === ConceptScheme::TYPE || $rdfType === SkosCollection::TYPE || $rdfType === RelationType::TYPE) {
            if ($this->init['custom.backward_compatible']) {
                $setName = 'collection';
            } else {
                $setName = 'set';
            }

            if (empty($params[$setName])) {
                throw new InvalidArgumentException("No $setName specified in the request parameters", 400);
            }

            $code = $params[$setName];
            $set = $this->manager->fetchByCode($code, Set::TYPE);
            if (null === $set) {
                throw new InvalidArgumentException(
                "No such $setName `$code`", 404
                );
            }
            $publishers = $set->getProperty(DcTerms::PUBLISHER);
            if (count($publishers) === 1) {
                if ($publishers[0]->getUri() !== $tenant->getUri()) {
                    throw new InvalidArgumentException(
                    "No such $setName `$code` in this tenant.", 404
                    );
                }
                return $set;
            } else {
                throw new InvalidArgumentException(
                "Something went very wrong: specified set $code has ill rdf presentation in the triple store, either no, or multiple tenant, whereas there must be exactly one"
                );
            }
        } else {
            return null;
        }
    }

    /**
     * Removed getErrorResponse function definition because it is already declared in ApiResponseTrait
     */

    /**
     * Get success response
     *
     * @param string $message
     * @param int    $status
     * @return ResponseInterface
     */
    private function getSuccessResponse($message, $status = 200)
    {
        $stream = new Stream('php://memory', 'wb+');
        $stream->write($message);
        $response = (new Response($stream, $status))
            ->withHeader('Content-Type', 'text/xml; charset="utf-8"');
        return $response;
    }

    /**
     * Check if we need to generate or not concept identifiers (uri).
     * Validates any existing identifiers.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \OpenSkos2\Resource $resource
     * @return bool If an uri must be autogenerated
     * @throws InvalidArgumentException
     */
    private function checkResourceIdentifiers(ServerRequestInterface $request, $resource)
    {
        $params = $request->getQueryParams();

        // We return if an uri must be autogenerated
        $autoGenerateIdentifiers = false;
        if (!empty($params['autoGenerateIdentifiers'])) {
            $autoGenerateIdentifiers = filter_var(
                $params['autoGenerateIdentifiers'], FILTER_VALIDATE_BOOLEAN
            );
        }

        $uuids = $resource->getProperty(OpenSkos::UUID);
        if ($autoGenerateIdentifiers) {
            if (!$resource->isBlankNode()) {
                throw new InvalidArgumentException(
                'Parameter autoGenerateIdentifiers is set to true, but the '
                . 'xml already contains uri (rdf:about).', 400
                );
            }
            if (count($uuids) > 0) {
                throw new InvalidArgumentException(
                'Parameter autoGenerateIdentifiers is set to true, but the '
                . 'xml already contains uuid.', 400
                );
            }
        } else {
            // Is uri missing
            if ($resource->isBlankNode()) {
                throw new InvalidArgumentException(
                'Uri (rdf:about) is missing from the xml. You may consider using autoGenerateIdentifiers.', 400
                );
            }
            // Is uuid missing?
            if (count($uuids) < 1) {
                throw new InvalidArgumentException(
                'Uuid is missing from the xml. You may consider using autoGenerateIdentifiers.', 400
                );
            }
        }

        return $autoGenerateIdentifiers;
    }

    /**
     * @param array $params
     * @return Tenanthrows InvalidArgumentException
     */
    private function getTenantFromParams($params)
    {
        if (empty($params['tenant'])) {
            throw new InvalidArgumentException('No tenant specified', 400);
        }

        return $this->getTenant($params['tenant'], $this->manager);
    }

    /**
     *
     * @param array $params
     * @return OpenSKOS_Db_Table_Row_User
     * @throws InvalidArgumentException
     */
    private function getUserFromParams($params)
    {
        if (empty($params['key'])) {
            throw new InvalidArgumentException('No key specified', 400);
        }
        return $this->getUserByKey($params['key']);
    }

    public function getResourceListResponse($params)
    {
        try {
            $index = $this->getResourceList($params);

            // augmenting with tenants and sets when necessary
            $rdfType = $this->manager->getResourceType();
            if ($rdfType === Concept::TYPE) {
                throw new ApiException('Straightforward return of list of all concepts is not implemented to avoid long-lasting response due to huge amount of concepts', 501);
            } else {
                if ($rdfType === ConceptScheme::TYPE || $rdfType === SkosCollection::TYPE) {
                    foreach ($index as $resource) {
                        $resource = $this->manager->augmentResourceWithTenant($resource);
                    }
                }
            }

            $result = new ResourceResultSet(
                $index, count($index), 1, $this->init["custom.maximal_rows"]
            );

            switch ($params['context']) {
                case 'json':
                    $response = (new JsonResponse($result, $rdfType, []))->getResponse();
                    break;
                case 'jsonp':
                    $response = (new JsonpResponse(
                        $result, $rdfType, $params['callback'], []
                        ))->getResponse();
                    break;
                case 'rdf':
                    $response = (new RdfResponse($result, $rdfType, []))->getResponse();
                    break;
                default:
                    throw new InvalidArgumentException('Invalid context: ' . $params['context']);
            }
            return $response;
        } catch (\Exception $e) {
            return $this->getErrorResponse(500, $e->getMessage());
        }
    }

    private function getResourceList($params)
    {
        $resType = $this->manager->getResourceType();
        if ($resType === Set::TYPE && $params['allow_oai'] !== null) {
            $index = $this->manager->fetchAllSets($params['allow_oai']);
        } else {
            $index = $this->manager->fetch();
        }
        return $index;
    }

    /*   Returns a mapping resource's titles to the resource's Uri
     *   Works for tenant, set, concept scheme, skos colllection, relation type
     */

    public function mapNameSearchID()
    {
        $index = $this->manager->fetchNameUri();
        return $index;
    }

    private function getTenantAndSetFromParams($params)
    {
        if ($this->manager->getResourceType() !== Tenant::TYPE) {
            $tenant = $this->getTenantFromParams($params);
            $tenantUri = $tenant->getUri();
            if ($this->manager->getResourceType() !== Set::TYPE) {
                $set = $this->getSet($params, $tenant);
                $setUri = $set->getUri();
            } else {
                $set = null;
                $setUri = null;
            }
        } else {
            $tenant = null;
            $tenantUri = null;
            $set = null;
            $setUri = null;
        }
        $retVal = [];
        $retVal['tenant'] = $tenant;
        $retVal['tenantUri'] = $tenantUri;
        $retVal['set'] = $set;
        $retVal['setUri'] = $setUri;
        return $retVal;
    }

    private function setTenantForSet(Tenant $inloggedTenant, $set, $doc)
    {
        // Meeertens: a literal code for tenants and sets is used for exchange with backward compatible API but it is not stored in the triple store, where only Uri are used to refer to other resources
        if ($this->init['custom.backward_compatible']) {
            $xmlTenantCode = $doc->documentElement->getAttributeNS(OpenSkos::NAME_SPACE, 'tenant'); // literal, code
            $xmlTenant = $this->getTenant($xmlTenantCode, $this->manager);
            $xmlTenantUri = $xmlTenant->getUri();
        } else {
            $xmlTenantUri = $doc->documentElement->getAttributeNS(DcTerms::NAME_SPACE, 'publisher'); // uri 
        }

        if (!$set->getTenantUri() && !empty($xmlTenantUri)) {
            $set->addUniqueProperty(DcTerms::PUBLISHER, new Uri($xmlTenantUri));
        }
        // If there still is no tenant add it from the query params
        if (!$set->getTenantUri()) {
            $set->addUniqueProperty(DcTerms::PUBLISHER, new Uri($inloggedTenant->getUri()));
        }
        if (!$set->getTenantUri()) {
            throw new InvalidArgumentException('No tenant specified in the xml body', 400);
        }
        return $set;
    }

}
