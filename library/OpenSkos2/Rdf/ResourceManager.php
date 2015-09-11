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
     * @param Resource $resource
     * @throws ResourceNotFoundException
     */
    public function update(Resource $resource)
    {

    }

    /**
     * @param Uri $resource
     */
    public function delete(Uri $resource)
    {
        $this->client->update("DELETE WHERE {<{$resource->getUri()}> ?predicate ?object}");
    }
    
    /**
     * @param Object[] $spec
     */
    public function deleteBy($spec)
    {
        $query = "DELETE WHERE {\n ?subject ";
        foreach ($spec as $predicate => $value) {
            $query .= "<{$predicate}> " . $this->valueToTurtle($value) . ";\n";
        }
        $query .= "?predicate ?object\n}";

        $this->client->update($query);
    }
    
    /**
     * Fetches a single resource matching the uri.
     * @param string $uri
     * @return Resource
     * @throws ResourceNotFoundException
     */
    public function fetchByUri($uri)
    {
        // @TODO Add the graph here in the query.
        $result = $this->client->query('DESCRIBE <' . $uri . '>');
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
     * @param Object[] $spec Example: [Skos::NOTATION => new Literal('AM002'),]
     * @param int $offset
     * @param int $limit
     * @return ResourceCollection
     */
    public function fetchBy($spec = [], $offset = null, $limit = null)
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
        $query .= 'WHERE { ' . PHP_EOL;
        if (!empty($spec)) {
            foreach ($spec as $predicate => $value) {
                $query .= '?subject <' . $predicate . '> ' . $this->valueToTurtle($value) . '.' . PHP_EOL;
            }
        } else {
            // All subjects
            $query .= '?subject ?predicate ?object' . PHP_EOL;
        }
        $query .= '}'; // end where
        
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
        
        $resources = $this->fetch($query);
        
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
     * Asks for if the properties map has a match.
     * Example for $matchProperties:
     * <code>
     * $matchProperties = [
     *     Skos::NOTATION => $concept->getProperty(Skos::NOTATION),
     *     Skos::INSCHEME => $concept->getProperty(Skos::INSCHEME),
     * ];
     * </code>
     * @param Object[] $matchProperties
     * @param string $excludeUri Adds filter for the subject != $excludeUri
     * @return boolean
     */
    public function askForMatch($matchProperties, $excludeUri = null)
    {
        $patterns = '';
        
        $ind = 0;
        foreach ($matchProperties as $predicate => $objects) {
            if (!is_array($objects)) {
                $objects = [$objects];
            }
            
            $patterns .= '?subject <' . $predicate . '> ?o' . $ind;
            $patterns .= PHP_EOL;                        
            $patterns .= 'FILTER (?o' . $ind . ' IN (' .(new NTriple())->serializeArray($objects) . '))';
            $patterns .= PHP_EOL;
            
            $ind ++;
        }
        
        if (!empty($excludeUri)) {
            $patterns .= 'FILTER (?subject != <' . $excludeUri . '>)';
            $patterns .= PHP_EOL;
        }
        
        return $this->ask($patterns);
    }

    /**
     * Fetch all resources matching the query.
     * @param string $query
     * @return ResourceCollection
     */
    protected function fetch($query)
    {
        $result = $this->client->query($query);
        return EasyRdf::graphToResourceCollection($result, $this->resourceType);
    }
    
    /**
     * Sends an ask query for if a match is found for the patterns and returns the boolean result.
     * @param string $patterns String representation of the patterns.
     * @return boolean
     */
    protected function ask($patterns)
    {
        $query = 'ASK {' . $patterns . '}';
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
