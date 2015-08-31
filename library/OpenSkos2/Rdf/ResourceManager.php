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
     * @param Resource $resource
     */
    public function delete(Resource $resource)
    {

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
}
