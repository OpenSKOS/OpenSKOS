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


use OpenSkos2\Bridge\EasyRdf;
use OpenSkos2\Exception\ResourceAlreadyExistsException;
use OpenSkos2\Exception\ResourceNotFoundException;

class ResourceManager
{
    /**
     * @var \EasyRdf_Sparql_Client
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
     * @param \EasyRdf_Sparql_Client $client
     * @param string $graph
     */
    public function __construct(\EasyRdf_Sparql_Client $client, $graph = null)
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
     * Fetch all resources matching the query.
     * @param string $query
     * @return ResourceCollection
     */
    public function fetch($query)
    {
        $result = $this->client->query($query);
        return EasyRdf::graphToResourceCollection($result, $this->resourceType);
    }
    
    /**
     * Adds limit and/or offset to the query.
     * @param string $query
     * @param int $offset
     * @param int $limit
     * @return ResourceCollection
     */
    public function fetchWithLimit($query, $offset = null, $limit = null)
    {
        if ($limit !== null) {
            $query .= PHP_EOL . 'LIMIT ' . $limit;
        }
        
        if ($offset !== null) {
            $query .= PHP_EOL . 'OFFSET ' . $offset;
        }
        
        return $this->fetch($query);
    }
    
    /**
     * Fetches a single resource matching the uri.
     * @param string $uri
     * @return Resource
     * @throws ResourceNotFoundException
     * @throws RuntimeException
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
     * @param Object[] $spec
     */
    public function deleteBy($spec)
    {
        $query = "DELETE WHERE {\n ?subject ";
        foreach ($spec as $property => $value) {
            $query .= "<{$property}> " . $this->valueToTurtle($value) . ";\n";
        }
        $query .= "?predicate ?object\n}";

        $this->client->update($query);
    }

    /**
     * @param Object $object
     * @return string
     * @throws \EasyRdf_Exception
     */
    protected function valueToTurtle(Object $object)
    {
        $serializer = new \EasyRdf_Serialiser_Ntriples();
        if ($object instanceof Literal) {
            return $serializer->serialiseValue([
                'type' => 'literal',
                'value' => $object->getValue(),
                'lang' => $object->getLanguage()
            ]);
        } elseif ($object instanceof Uri) {
            return $serializer->serialiseValue(['type' => 'uri', 'value' => $object->getUri()]);
        }
    }

    /**
     * @param Object[] $spec
     * @return ResourceCollection
     */
    public function fetchBy($spec)
    {
        $query = "DELETE WHERE {\n ?subject ";
        foreach ($spec as $property => $value) {
            $query .= "<{$property}> " . $this->valueToTurtle($value) . ";\n";
        }
        $query .= "?predicate ?object\n}";
        return self::fetch($query);
    }
    
    /**
     * Fetch list of namespaces which are used in the resources in the query.
     * @param string $query
     * @return ResourceCollection
     * @throws RuntimeException
     */
    public function fetchNamespaces($query = 'DESCRIBE ?object')
    {
        $query .= PHP_EOL . ' LIMIT 0';
        
        // The EasyRdf_Sparql_Client does not support fetching the namespaces, fuseki does.
        // @TODO DI
        $httpClient = \EasyRdf_Http::getDefaultHttpClient();
        $httpClient->resetParameters();
        
        // @TODO Post for big queries
        $httpClient->setMethod('GET');
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
}
