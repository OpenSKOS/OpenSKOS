<?php

/**
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

namespace OpenSkos2\Rdf;

use EasyRdf\Http;
use EasyRdf\Sparql\Client;
use OpenSkos2\Bridge\EasyRdf;
use OpenSkos2\Rdf\Object as RdfObject;
use OpenSkos2\Exception\ResourceAlreadyExistsException;
use OpenSkos2\Exception\ResourceNotFoundException;
use OpenSkos2\Rdf\Serializer\NTriple;
use OpenSkosNamespace as OpenSkosNamespace;

// @TODO A lot of things can be made without working with full documents, so that should not go through here
// For example getting a list of pref labels and uris

class ResourceManager
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var string
     */
    protected $graph;

    /**
     * What is the basic resource for this manager.
     * Made to be extended and overwrited.
     * @var string NULL means any resource.
     */
    protected $resourceType = null;

    /**
     * ResourceManager constructor.
     * @param Client $client
     * @param string $graph
     */
    public function __construct(Client $client, $graph = null)
    {
        $this->client = $client;
        $this->graph = $graph;
    }

    /**
     * @param Resource $resource
     * @throws ResourceAlreadyExistsException
     */
    public function insert(Resource $resource)
    {
        $this->client->insert(EasyRdf::resourceToGraph($resource));
    }
    
    /**
     * Soft delete resource , sets the openskos:status to deleted
     * and add a delete date
     *
     * @param \OpenSkos2\Rdf\Resource $resource
     * @param Uri $user
     */
    public function deleteSoft(Resource $resource, Uri $user = null)
    {
        $resource->unsetProperty(OpenSkosNamespace::STATUS);
        $status = new Literal(\OpenSkos2\Concept::STATUS_DELETED);
        $resource->addProperty(OpenSkosNamespace::STATUS, $status);
        
        $resource->unsetProperty(OpenSkosNamespace::DATE_DELETED);
        $resource->addProperty(OpenSkosNamespace::DATE_DELETED, new Literal(date('c'), null, Literal::TYPE_DATETIME));
        
        if ($user) {
            $resource->unsetProperty(OpenSkosNamespace::DELETEDBY, $user);
        }
        
        $this->delete($resource);
        $this->insert($resource);
    }

    /**
     * @param Uri $resource
     */
    public function delete(Uri $resource)
    {
        $this->client->update("DELETE WHERE {<{$resource->getUri()}> ?predicate ?object}");
    }

    /**
     * @param Object[] $simplePatterns
     */
    public function deleteBy($simplePatterns)
    {
        $query = "DELETE WHERE {\n ?subject ";
        foreach ($simplePatterns as $predicate => $value) {
            $query .= "<{$predicate}> " . $this->valueToTurtle($value) . ";\n";
        }
        $query .= "?predicate ?object\n}";

        $this->client->update($query);
    }
    
    /**
     * Fetch resource by uuid
     *
     * @param string $uuid
     * @return OpenSkos2\Rdf\Resource
     */
    public function fetchByUuid($uuid)
    {
        $prefixes = [
            'openskos' => OpenSkosNamespace::NAME_SPACE,
        ];

        $lit = new \OpenSkos2\Rdf\Literal($uuid);
        $qb = new \Asparagus\QueryBuilder($prefixes);
        $query = $qb->describe('?subject')
            ->where('?subject', 'openskos:uuid', (new \OpenSkos2\Rdf\Serializer\NTriple)->serialize($lit));
        $data = $this->fetchQuery($query);
        
        if (!isset($data[0])) {
            return;
        }
        return $data[0];
    }

    /**
     * Fetches a single resource matching the uri.
     * @param string $uri
     * @return Resource
     * @throws ResourceNotFoundException
     */
    public function fetchByUri($uri)
    {
        $resource = new Uri($uri);
        $result = $this->client->query('DESCRIBE '. (new NTriple)->serialize($resource));
        $resources = EasyRdf::graphToResourceCollection($result, $this->resourceType);

        if (count($resources) == 0) {
            throw new ResourceNotFoundException(
                'The requested resource <' . $uri . '> was not found.'
            );
        }

        if (count($resources) > 1) {
            throw new \RuntimeException(
                'Something went very wrong. The requested resource <' . $uri . '> is found twice.'
            );
        }

        return $resources[0];
    }

    /**
     * Fetches full resources.
     * There is hardcoded order by uri.
     * @param Object[] $simplePatterns Example: [Skos::NOTATION => new Literal('AM002'),]
     * @param int $offset
     * @param int $limit
     * @return ResourceCollection
     */
    public function fetch($simplePatterns = [], $offset = null, $limit = null)
    {
        /*
        DESCRIBE ?subject {
            SELECT DISTINCT ?subject
            WHERE {
                ?subject ?predicate ?object
            }
            ORDER BY ?subject
            LIMIT 10
            OFFSET 0
        }
        */

        $query = 'DESCRIBE ?subject {' . PHP_EOL;

        $query .= 'SELECT DISTINCT ?subject' . PHP_EOL;
        $query .= 'WHERE { ' . $this->simplePatternsToQuery($simplePatterns, '?subject') . ' }';

        // We need some order
        // @TODO provide possibility to order on other predicates.
        // This will need to create ?subject ?predicate ?o1 .... ORDER BY ?o1
        $query .= PHP_EOL . 'ORDER BY ?subject';

        if ($limit !== null) {
            $query .= PHP_EOL . 'LIMIT ' . $limit;
        }

        if ($offset !== null) {
            $query .= PHP_EOL . 'OFFSET ' . $offset;
        }

        $query .= '}'; // end sub select

        $resources = $this->fetchQuery($query);

        // The order by part does not apply to the resources with describe.
        // So we need to order them again.
        // @TODO Find other solution - sort in jena, not here.
        // @TODO provide possibility to order on other predicates.
        $resources->uasort(
            function (Resource $resource1, Resource $resource2) {
                return strcmp($resource1->getUri(), $resource2->getUri());
            }
        );

        return $resources;
    }

    /**
     * Fetch list of namespaces which are used in the resources in the query.
     * @return ResourceCollection
     */
    public function fetchNamespaces()
    {
        $query = 'DESCRIBE ?subject';
        $query .= PHP_EOL . ' LIMIT 0';

        // The EasyRdf\Sparql\Client does not gets the namespaces which fuseki provides.
        // Maybe it can be fixed/configured. Then this method can use the client directly.
        // @TODO DI
        $httpClient = Http::getDefaultHttpClient();
        $httpClient->resetParameters();

        $httpClient->setMethod('GET'); // @TODO Post for big queries
        $uri = $this->client->getQueryUri() . '?query=' . urlencode($query) . '&format=json';
        $httpClient->setUri($uri);

        $response = $httpClient->request();

        if (!$response->isSuccessful()) {
            throw new \RuntimeException(
                'HTTP request to ' . $uri . ' for getting namespaces failed: ' . $response->getBody()
            );
        }

        return json_decode($response->getBody(), true)['@context'];
    }

    /**
     * Counts distinct resources
     * @param Object[] $simplePatterns Example: [Skos::NOTATION => new Literal('AM002'),]
     * @return int
     */
    public function countResources($simplePatterns = [])
    {
        $query = 'SELECT (COUNT(DISTINCT ?subject) AS ?count)' . PHP_EOL;
        $query .= 'WHERE { ' . $this->simplePatternsToQuery($simplePatterns, '?subject') . ' }';

        /* @var $result EasyRdf\Sparql\Result */
        $result = $this->client->query($query);

        return $result[0]->count->getValue();
    }

    /**
     * Asks for if the properties map has a match.
     * Example for $matchProperties:
     *
     * <code>
     * $matchProperties = [
     *     [
     *       "predicate" => Skos::NOTATION
     *       "value" => $concept->getProperty(Skos::NOTATION),
     *       "operator" => "=" // optional defaults to equals
     *     ],
     *     [
     *       "predicate" => Skos::INSCHEME
     *       "value" => $concept->getProperty(Skos::INSCHEME),
     *       "operator" => "!="
     *     ]
     * ];
     * </code>
     *
     * @param array $params
     * @param string $excludeUri
     * @return boolean
     */
    public function askForMatch(array $params, $excludeUri = null)
    {
        $select = '';
        $filter = 'FILTER(' . PHP_EOL;

        $filters = [];
        foreach ($params as $i => $data) {
            $predicate = $data['predicate'];
            $operator = '=';

            if (isset($data['operator'])) {
                $operator = $data['operator'];
            }

            $select .= '?subject <' . $predicate . '> ?' . $i . '. ' . PHP_EOL;

            $value = $data['value'];
            if (!is_array($value)) {
                $value = [$value];
            }
            
            $newFilter = [];
            foreach ($value as $val) {
                $newFilter[] = '?' . $i . ' ' . $operator . ' ' . (new NTriple())->serialize($val);
            }
            
            $filters[] = '(' . implode(' || ', $newFilter) . ') ';
        }

        $filter .= implode(' && ', $filters) . ' ';

        if ($excludeUri) {
            $uri = new Uri($excludeUri);
            $filter .= '&& ?subject != ' . (new NTriple())->serialize($uri);

        }

        $ask = $select . $filter . ')';

        return $this->ask($ask);
    }
    
    /**
     * Makes query (with full sparql patterns) from our search patterns.
     * @param Object[] $simplePatterns Example: [Skos::NOTATION => new Literal('AM002'),]
     * @param string $subject
     * @return string
     */
    protected function simplePatternsToQuery($simplePatterns, $subject)
    {
        $query = '';
        if (!empty($simplePatterns)) {
            foreach ($simplePatterns as $predicate => $value) {
                $query .= $subject . ' <' . $predicate . '> ' . $this->valueToTurtle($value) . '.' . PHP_EOL;
            }
        } else {
            // All subjects
            $query .= $subject . ' ?predicate ?object' . PHP_EOL;
        }

        return $query;
    }

    /**
     * Execute raw query
     *
     * @param string $query
     * @return \EasyRdf\Graph
     */
    public function query($query)
    {
        return $this->client->query($query);
    }

    /**
     * Fetch all resources matching the query.
     *
     * @param \Asparagus\QueryBuilder|string $query
     * @return ResourceCollection
     */
    public function fetchQuery($query)
    {
        if ($query instanceof \Asparagus\QueryBuilder) {
            $query = $query->getSPARQL();
        }
        
        $result = $this->client->query($query);
        return EasyRdf::graphToResourceCollection($result, $this->resourceType);
    }
    
    /**
     * Sends an ask query for if a match is found for the patterns and returns the boolean result.
     * @param string $query String representation of the patterns.
     * @return boolean
     */
    public function ask($query)
    {
        $query = 'ASK {' . PHP_EOL . $query . PHP_EOL . '}';
        return $this->client->query($query)->getBoolean();
    }

    /**
     * @param Object $object
     * @return string
     * @throws \EasyRdf\Exception
     */
    protected function valueToTurtle(Object $object)
    {
        $serializer = new NTriple();
        return $serializer->serialize($object);
    }
}
