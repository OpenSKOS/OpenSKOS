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

use OpenSkos2\Api\Exception\ApiException;
use OpenSkos2\Api\Exception\NotFoundException;
use OpenSkos2\Converter\Text;
use OpenSkos2\Namespaces;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Namespaces\Rdf;
use OpenSKOS_Db_Table_Row_Collection;
use OpenSkos2\Api\Exception\InvalidArgumentException;
use OpenSkos2\Api\Response\ResultSet\JsonResponse;
use OpenSkos2\Api\Response\ResultSet\JsonpResponse;
use OpenSkos2\Api\Response\ResultSet\RdfResponse;
use OpenSkos2\Api\Response\Detail\JsonResponse as DetailJsonResponse;
use OpenSkos2\Api\Response\Detail\JsonpResponse as DetailJsonpResponse;
use OpenSkos2\Api\Response\Detail\RdfResponse as DetailRdfResponse;
use OpenSkos2\Api\Exception\InvalidPredicateException;
use OpenSkos2\Rdf\ResourceManager;
use OpenSkos2\ConceptManager;
use OpenSkos2\FieldsMaps;
use OpenSkos2\Validator\Resource as ResourceValidator;
use OpenSkos2\Tenant as Tenant;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\Stream;

/**
 * Map an API request from the old application to still work with the new backend on Jena
 */
class Concept
{
    use \OpenSkos2\Api\Response\ApiResponseTrait;
    
    const QUERY_DESCRIBE = 'describe';
    const QUERY_COUNT = 'count';

    /**
     * Resource manager
     *
     * @var \OpenSkos2\Rdf\ResourceManager
     */
    private $manager;
    
    /**
     * Concept manager
     *
     * @var \OpenSkos2\ConceptManager
     */
    private $conceptManager;
    
    /**
     * Search autocomplete
     *
     * @var \OpenSkos2\Search\Autocomplete
     */
    private $searchAutocomplete;

    /**
     * Amount of concepts to return
     *
     * @var int
     */
    private $limit = 20;

    /**
     *
     * @param \OpenSkos2\Rdf\ResourceManager $manager
     * @param \OpenSkos2\ConceptManager $conceptManager
     * @param \OpenSkos2\Search\Autocomplete $searchAutocomplete
     */
    public function __construct(
        ResourceManager $manager,
        ConceptManager $conceptManager,
        \OpenSkos2\Search\Autocomplete $searchAutocomplete
    ) {
        $this->manager = $manager;
        $this->conceptManager = $conceptManager;
        $this->searchAutocomplete = $searchAutocomplete;
    }

    /**
     * Map the following requests
     *
     * /api/find-concepts?q=Kim%20Holland
     * /api/find-concepts?&fl=prefLabel,scopeNote&format=json&q=inScheme:"http://uri"
     * /api/find-concepts?format=json&fl=uuid,uri,prefLabel,class,dc_title&id=http://data.beeldengeluid.nl/gtaa/27140
     * /api/concept/82c2614c-3859-ed11-4e55-e993c06fd9fe.rdf
     *
     * @param ServerRequestInterface $request
     * @param string $context
     * @return ResponseInterface
     */
    public function findConcepts(ServerRequestInterface $request, $context)
    {
        $params = $request->getQueryParams();
        
        // offset
        $start = 0;
        if (!empty($params['start'])) {
            $start = (int)$params['start'];
        }

        // limit
        $limit = $this->limit;
        if (isset($params['rows']) && $params['rows'] < 1001) {
            $limit = (int)$params['rows'];
        }
        
        $options = [
            'start' => $start,
            'rows' => $limit,
            'status' => [\OpenSkos2\Concept::STATUS_CANDIDATE, \OpenSkos2\Concept::STATUS_APPROVED],
        ];
        
        // tenant
        if (isset($params['tenant'])) {
            $tenant = $this->getTenantFromParams($params);
            $options['tenants'] = [$tenant->code];
        }
        
        // search query
        if (isset($params['q'])) {
            $options['searchText'] = $params['q'];
        }
        
        $concepts = $this->searchAutocomplete->search($options, $total);
        
        $result = new ResourceResultSet($concepts, $total, $start, $limit);
        
        if (isset($params['fl'])) {
            $propertiesList = $this->fieldsListToProperties($params['fl']);
        } else {
            $propertiesList = [];
        }
        
        switch ($context) {
            case 'json':
                $response = (new JsonResponse($result, $propertiesList))->getResponse();
                break;
            case 'jsonp':
                $response = (new JsonpResponse($result, $params['callback'], $propertiesList))->getResponse();
                break;
            case 'rdf':
                $response = (new RdfResponse($result, $propertiesList))->getResponse();
                break;
            default:
                throw new InvalidArgumentException('Invalid context: ' . $context);
        }

        return $response;
    }

    /**
     * Get PSR-7 response for concept
     *
     * @param $request \Psr\Http\Message\ServerRequestInterface
     * @param string $context
     * @throws NotFoundException
     * @throws InvalidArgumentException
     * @return ResponseInterface
     */
    public function getConceptResponse(ServerRequestInterface $request, $uuid, $context)
    {
        $concept = $this->getConcept($uuid);

        $params = $request->getQueryParams();
        
        if (isset($params['fl'])) {
            $propertiesList = $this->fieldsListToProperties($params['fl']);
        } else {
            $propertiesList = [];
        }
        
        switch ($context) {
            case 'json':
                $response = (new DetailJsonResponse($concept, $propertiesList))->getResponse();
                break;
            case 'jsonp':
                $response = (new DetailJsonpResponse($concept, $params['callback'], $propertiesList))->getResponse();
                break;
            case 'rdf':
                $response = (new DetailRdfResponse($concept, $propertiesList))->getResponse();
                break;
            default:
                throw new InvalidArgumentException('Invalid context: ' . $context);
        }

        return $response;
    }

    /**
     * Get openskos concept
     *
     * @param string|\OpenSkos2\Rdf\Uri $id
     * @throws NotFoundException
     * @throws Exception\DeletedException
     * @return \OpenSkos2\Concept
     */
    public function getConcept($id)
    {
        /* @var $concept \OpenSkos2\Concept */
        if ($id instanceof \OpenSkos2\Rdf\Uri) {
            $concept = $this->manager->fetchByUri($id);
        } else {
            $concept = $this->manager->fetchByUuid($id);
        }
        
        if (!$concept) {
            throw new NotFoundException('Concept not found by id: ' . $id, 404);
        }

        if ($concept->isDeleted()) {
            throw new Exception\DeletedException('Concept ' . $id . ' is deleted', 410);
        }

        return $concept;
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
     * Update a concept
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function update(ServerRequestInterface $request)
    {
        try {
            $concept = $this->getConceptFromRequest($request);
            $existingConcept = $this->manager->fetchByUri((string)$concept->getUri());
            
            $params = $this->getParams($request);
                        
            $tenant = $this->getTenantFromParams($params);
            
            $collection = $this->getCollection($params, $tenant);
            $user = $this->getUserFromParams($params);
            
            $this->resourceEditAllowed($concept, $tenant, $user);
        
            $concept->ensureMetadata(
                $tenant->code,
                $collection->getUri(),
                $user->getFoafPerson(),
                $existingConcept->getStatus()
            );
            
            $this->validate($concept, $tenant);
            
            $this->conceptManager->replaceAndCleanRelations($concept);
        } catch (ApiException $ex) {
            return $this->getErrorResponse($ex->getCode(), $ex->getMessage());
        } catch (\OpenSkos2\Exception\ResourceNotFoundException $ex) {
            return $this->getErrorResponse(404, 'Concept not found try insert');
        }
        
        $xml = (new \OpenSkos2\Api\Transform\DataRdf($concept))->transform();
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
            /* @var $concept \OpenSkos2\Concept */
            $concept = $this->manager->fetchByUri($id);
            if (!$concept) {
                throw new NotFoundException('Concept not found by id :' . $id, 404);
            }
            
            if ($concept->isDeleted()) {
                throw new NotFoundException('Concept already deleted :' . $id, 410);
            }
            
            $user = $this->getUserFromParams($params);
            $tenant = $this->getTenantFromParams($params);
            
            $this->resourceEditAllowed($concept, $tenant, $user);
            
            $this->manager->deleteSoft($concept);
        } catch (ApiException $ex) {
            return $this->getErrorResponse($ex->getCode(), $ex->getMessage());
        }
        
        $xml = (new \OpenSkos2\Api\Transform\DataRdf($concept))->transform();
        return $this->getSuccessResponse($xml, 202);
    }
    
    /**
     * Gets a list (array or string) of fields and try to recognise the properties from it.
     * @param array $fieldsList
     * @return array
     */
    protected function fieldsListToProperties($fieldsList)
    {
        if (!is_array($fieldsList)) {
            $fieldsList = array_map('trim', explode(',', $fieldsList));
        }
        
        $propertiesList = [];
        $fieldsMap = FieldsMaps::getOldToProperties();
        
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
     * @param \OpenSkos2\Concept $concept
     * @param OpenSKOS_Db_Table_Row_Tenant $tenant
     * @throws InvalidArgumentException
     */
    protected function validate(\OpenSkos2\Concept $concept, \OpenSKOS_Db_Table_Row_Tenant $tenant)
    {
        $validator = new ResourceValidator(
            $this->manager,
            new Tenant($tenant->code)
        );
        
        
        if (!$validator->validate($concept)) {
            throw new InvalidArgumentException(implode(' ', $validator->getErrorMessages()), 400);
        }
    }
    
    /**
     * Handle the action of creating the concept
     *
     * @param ServerRequestInterface $request
     */
    private function handleCreate(ServerRequestInterface $request)
    {
        $concept = $this->getConceptFromRequest($request);
        
        if (!$concept->isBlankNode() && $this->manager->askForUri((string)$concept->getUri())) {
            throw new InvalidArgumentException(
                'The concept with uri ' . $concept->getUri() . ' already exists. Use PUT instead.',
                400
            );
        }
        
        $params = $this->getParams($request);
        
        $tenant = $this->getTenantFromParams($params);
        $collection = $this->getCollection($params, $tenant);
        $user = $this->getUserFromParams($params);
        
        $concept->ensureMetadata(
            $tenant->code,
            $collection->getUri(),
            $user->getFoafPerson()
        );
        
        $autoGenerateUri = $this->checkConceptIdentifiers($request, $concept);
        if ($autoGenerateUri) {
            $concept->selfGenerateUri(
                new Tenant($tenant->code),
                $this->conceptManager
            );
        }
        
        $this->validate($concept, $tenant);
        
        $this->manager->insert($concept);
        
        $rdf = (new Transform\DataRdf($concept))->transform();
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
        foreach (array('tenant', 'collection', 'key') as $attributeName) {
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
     * @return \OpenSkos2\Concept
     * @throws InvalidArgumentException
     */
    private function getConceptFromRequest(ServerRequestInterface $request)
    {
        $doc = $this->getDomDocumentFromRequest($request);

        $descriptions = $doc->documentElement->getElementsByTagNameNs(Rdf::NAME_SPACE, 'Description');
        if ($descriptions->length != 1) {
            throw new InvalidArgumentException(
                'Expected exactly one '
                . '/rdf:RDF/rdf:Description, got '.$descriptions->length,
                412
            );
        }

        // is a tenant, collection or api key set in the XML?
        foreach (array('tenant', 'collection', 'key') as $attributeName) {
            $value = $doc->documentElement->getAttributeNS(OpenSkos::NAME_SPACE, $attributeName);
            // remove the api key
            if (!empty($value) && $attributeName === 'key') {
                $doc->documentElement->removeAttributeNS(OpenSkos::NAME_SPACE, $attributeName);
            }
        }

        $resource = (new Text($doc->saveXML()))->getResources();

        if (!isset($resource[0]) || !$resource[0] instanceof \OpenSkos2\Concept) {
            throw new InvalidArgumentException('XML Could not be converted to SKOS Concept', 400);
        }

        /** @var $concept \OpenSkos2\Concept **/
        $concept = $resource[0];
        
        return $concept;
    }
    
    /**
     * Get dom document from request
     *
     * @param ServerRequestInterface $request
     * @return \DOMDocument
     * @throws InvalidArgumentException
     */
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
        
        return $doc;
    }

    /**
     * @params array $queryParams
     * @params \OpenSKOS_Db_Table_Row_Tenant $tenant
     * @return OpenSKOS_Db_Table_Row_Collection
     */
    private function getCollection($params, \OpenSKOS_Db_Table_Row_Tenant $tenant)
    {
        if (empty($params['collection'])) {
            throw new InvalidArgumentException('No collection specified', 412);
        }
        $code = $params['collection'];
        $model = new \OpenSKOS_Db_Table_Collections();
        $collection = $model->findByCode($code, $tenant);
        if (null === $collection) {
            throw new InvalidArgumentException(
                'No such collection `'.$code.'` in this tenant.',
                404
            );
        }
        return $collection;
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
     * @param \OpenSkos2\Concept $concept
     * @return boolean If an uri must be autogenerated
     */
    private function checkConceptIdentifiers(ServerRequestInterface $request, \OpenSkos2\Concept $concept)
    {
        $params = $request->getQueryParams();

        // We return if an uri must be autogenerated
        $autoGenerateIdentifiers = false;
        if (!empty($params['autoGenerateIdentifiers'])) {
            $autoGenerateIdentifiers = filter_var(
                $params['autoGenerateIdentifiers'],
                FILTER_VALIDATE_BOOLEAN
            );
        }

        if ($autoGenerateIdentifiers) {
            if (!$concept->isBlankNode()) {
                throw new InvalidArgumentException(
                    'Parameter autoGenerateIdentifiers is set to true, but the '
                    . 'xml already contains uri (rdf:about).',
                    400
                );
            }
        } else {
            // Is uri missing
            if ($concept->isBlankNode()) {
                throw new InvalidArgumentException(
                    'Uri (rdf:about) is missing from the xml. You may consider using autoGenerateIdentifiers.',
                    400
                );
            }
        }

        return $autoGenerateIdentifiers;
    }
    
    /**
     * @param array $params
     * @return \OpenSKOS_Db_Table_Row_Tenant
     * @throws InvalidArgumentException
     */
    private function getTenantFromParams($params)
    {
        if (empty($params['tenant'])) {
            throw new InvalidArgumentException('No tenant specified', 412);
        }
            
        return $this->getTenant($params['tenant']);
    }
    
    /**
     *
     * @param array $params
     * @return \OpenSKOS_Db_Table_Row_User
     * @throws InvalidArgumentException
     */
    private function getUserFromParams($params)
    {
        if (empty($params['key'])) {
            throw new InvalidArgumentException('No key specified', 412);
        }
        return $this->getUserByKey($params['key']);
    }
}
