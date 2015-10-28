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

namespace OpenSkos2\Bridge;

use EasyRdf\Graph;
use OpenSkos2\Collection;
use OpenSkos2\CollectionCollection;
use OpenSkos2\Concept;
use OpenSkos2\ConceptCollection;
use OpenSkos2\Person;
use OpenSkos2\Exception\InvalidArgumentException;
use OpenSkos2\Rdf\Literal;
use OpenSkos2\Rdf\Resource;
use OpenSkos2\Rdf\ResourceCollection;
use OpenSkos2\Rdf\Uri;

class EasyRdf
{
    /**
     * @param \EasyRdf\Graph $graph to $read
     * @param string $expectedType If expected type is set, a collection of that type will be enforced.
     * @return ResourceCollection
     */
    public static function graphToResourceCollection(Graph $graph, $expectedType = null)
    {
        $collection = self::createResourceCollection($expectedType);

        foreach ($graph->resources() as $resource) {
            /** @var $resource \EasyRdf\Resource */
            $type = $resource->get('rdf:type');

            // Filter out resources which are not fully described.
            if (!$type) {
                continue;
            }
            
            $myResource = self::createResource(
                $resource->getUri(),
                $type
            );

            foreach ($resource->propertyUris() as $propertyUri) {
                foreach ($resource->all(new \EasyRdf\Resource($propertyUri)) as $propertyValue) {
                    if ($propertyValue instanceof \EasyRdf\Literal) {
                        $myResource->addProperty(
                            $propertyUri,
                            new Literal(
                                $propertyValue->getValue(),
                                $propertyValue->getLang(),
                                $propertyValue->getDatatypeUri()
                            )
                        );
                    } elseif ($propertyValue instanceof \EasyRdf\Resource) {
                        $myResource->addProperty($propertyUri, new Uri($propertyValue->getUri()));
                    }
                }
            }

            $collection[] = $myResource;
        }
        return $collection;
    }
    
    /**
     * @param Resource $resource
     * @return Graph
     */
    public static function resourceToGraph(Resource $resource)
    {
        $graph = new Graph();
        
        self::addResourceToGraph($resource, $graph);

        return $graph;
    }
    
    /**
     * @param ResourceCollection $collection
     * @return Graph
     */
    public static function resourceCollectionToGraph(ResourceCollection $collection)
    {
        $graph = new Graph();
        
        foreach ($collection as $resource) {
            self::addResourceToGraph($resource, $graph);
        }

        return $graph;
    }
    
    /**
     * Creates a resource matching the give type.
     * @param string $uri
     * @param \EasyRdf\Resource|null $type
     * @return Resource
     */
    protected static function createResource($uri, $type)
    {
        if ($type) {
            switch ($type->getUri()) {
                case Concept::TYPE:
                    return new Concept($uri);
                case \OpenSkos2\ConceptScheme::TYPE:
                    return new \OpenSkos2\ConceptScheme($uri);
                case Collection::TYPE:
                    return new Collection($uri);
                case Person::TYPE:
                    return new Person($uri);
                default:
                    return new Resource($uri);
            }
        } else {
            return new Resource($uri);
        }
    }
    
    /**
     * Creates a resource collection for the desired type.
     * @param string $type
     * @param string $uri
     * @return Resource
     */
    protected static function createResourceCollection($type)
    {
        switch ($type) {
            case Concept::TYPE:
                return new ConceptCollection();
            case \OpenSkos2\ConceptScheme::TYPE:
                return new \OpenSkos2\ConceptSchemeCollection();
            case Collection::TYPE:
                return new CollectionCollection();
            default:
                return new ResourceCollection();
        }
    }

    /**
     * @param Resource $resource
     * @param \EasyRdf\Graph $graph
     * @throws InvalidArgumentException
     */
    protected static function addResourceToGraph(Resource $resource, \EasyRdf\Graph $graph)
    {
        $easyResource = new \EasyRdf\Resource($resource->getUri(), $graph);
        
        foreach ($resource->getProperties() as $propName => $property) {
            foreach ($property as $value) {
                /**
                 * @var $value Object
                 */
                if ($value instanceof Literal) {
                    $val = $value->getValue();
                    
                    // Convert timestamp to string
                    if ($val instanceof \DateTime) {
                        $val = $val->format(\DATE_W3C);
                    }
                    
                    $easyResource->addLiteral(
                        $propName,
                        new \EasyRdf\Literal($val, $value->getLanguage(), $value->getType())
                    );
                } elseif ($value instanceof Uri) {
                    $easyResource->addResource($propName, $value->getUri());
                } else {
                    throw new InvalidArgumentException(
                        "Unexpected value found for property {$propName} " . var_export($value)
                    );
                }
            }
        }
    }
}
