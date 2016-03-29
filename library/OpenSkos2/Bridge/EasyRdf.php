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

use DateTime;
use EasyRdf\Graph;
use EasyRdf\Literal as Literal2;
use EasyRdf\Resource as Resource2;
use OpenSkos2\Tenant;
use OpenSkos2\TenantCollection;
use OpenSkos2\Concept;
use OpenSkos2\ConceptCollection;
use OpenSkos2\ConceptScheme;
use OpenSkos2\ConceptSchemeCollection;
use OpenSkos2\Exception\InvalidArgumentException;
use OpenSkos2\Person;
use OpenSkos2\Rdf\Literal;
use OpenSkos2\Rdf\Resource;
use OpenSkos2\Rdf\ResourceCollection;
use OpenSkos2\Rdf\Uri;
use OpenSkos2\Set;
use OpenSkos2\SetCollection;
use OpenSkos2\SkosCollection;
use OpenSkos2\SkosCollectionCollection;
use OpenSkos2\SkosXl\Label;
use OpenSkos2\SkosXl\LabelCollection;
use OpenSkos2\Namespaces\Rdf;

class EasyRdf {

    /**
     * @param Graph $graph to $read
     * @param string $expectedType If expected type is set, a collection of that type will be enforced.
     * @return ResourceCollection
     */
    public static function graphToResourceCollection(Graph $graph, $expectedType = null) {
        //var_dump($graph);
        $collection = self::createResourceCollection($expectedType);

        // make two lists: the types resources which are main resources (roots)
        // and typeless resources which are to be used as subresources (elements of complex types in xml)
        $mainResources = array();
        $subResources = array();
        foreach ($graph->resources() as $resource) {
            $type = $resource->get('rdf:type');
            if (!$type) {
                $subResources[] = $resource;
            } else {
                $mainResources[] = $resource;
            }
        }
        
        // now compose main resources;
        $myResource = null;
        foreach ($mainResources as $resource) {
            $type = $resource->get('rdf:type');
            $myResource = self::createResource(
                            $resource->getUri(), $type
            );
            self::makeNode($myResource, $resource, $subResources);
        }
        if ($myResource !== null) {
            $collection[] = $myResource;
        }

        return $collection;
    }

    private static function makeNode(&$myResource, Resource2 $resource, $subResources){
        $propertyUris = $resource->propertyUris();
        // type has been already identified and set in the resource constructor
        // we do no need to duplicate it 
        $typeIndex = array_search(Rdf::TYPE, $propertyUris);
        if ($typeIndex){
            unset($propertyUris[$typeIndex]);
        }
        unset($propertyUris[Rdf::TYPE]);
        foreach ($propertyUris as $propertyUri) {
                foreach ($resource->all(new Resource2($propertyUri)) as $propertyValue) {
                    if ($propertyValue instanceof Literal2) {
                        $myResource->addProperty(
                            $propertyUri,
                            new Literal(
                                $propertyValue->getValue(),
                                $propertyValue->getLang(),
                                $propertyValue->getDatatypeUri()
                            )
                        );
                    } elseif ($propertyValue instanceof Resource2) {
                        // recursion
                        $mySubResource = self::createResource($propertyValue->getUri(), null);
                        self::makeNode($mySubResource, $propertyValue, $subResources);
                        $myResource->addProperty($propertyUri,  $mySubResource);
                    }
                }
            }
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
     * @param Resource2|null $type
     * @return Resource
     */
    protected static function createResource($uri, $type)
    {
        //var_dump($type->getUri());
        //var_dump(Tenant::TYPE);
        if ($type) {
            switch ($type->getUri()) {
                case Tenant::TYPE:
                    return new Tenant($uri);
                case Concept::TYPE:
                    return new Concept($uri);
                case ConceptScheme::TYPE:
                    return new ConceptScheme($uri);
                case Set::TYPE:
                    return new Set($uri);
                case Person::TYPE:
                    return new Person($uri);
                case Label::TYPE:
                    return new Label($uri);
                case SkosCollection::TYPE:
                    return new SkosCollection($uri);
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
    public static function createResourceCollection($type)
    {
        //var_dump($type);
        //var_dump(Tenant::TYPE);
        switch ($type) {
            case Tenant::TYPE:
                return new TenantCollection();
            case Concept::TYPE:
                return new ConceptCollection();
            case ConceptScheme::TYPE:
                return new ConceptSchemeCollection();
            case Set::TYPE:
                return new SetCollection();
            case Label::TYPE:
                return new LabelCollection();
            case SkosCollection::TYPE:
                return new SkosCollectionCollection();
            default:
                return new ResourceCollection();
        }
    }

    /**
     * @param Resource $resource
     * @param Graph $graph
     * @throws InvalidArgumentException
     */
    protected static function addResourceToGraph(Resource $resource, Graph $graph)
    {
        $easyResource = new Resource2($resource->getUri(), $graph);
        
        foreach ($resource->getProperties() as $propName => $property) {
            foreach ($property as $value) {
                /**
                 * @var $value Object
                 */
                if ($value instanceof Literal) {
                    $val = $value->getValue();
                    
                    // Convert timestamp to string
                    if ($val instanceof DateTime) {
                        $val = $val->format(\DATE_W3C);
                    }
                    
                    $easyResource->addLiteral(
                        $propName,
                        new Literal2($val, $value->getLanguage(), $value->getType())
                    );
                } else if ($value instanceof Uri) {
                        $easyResource->addResource($propName, trim($value->getUri())); 
                       if ($value instanceof Resource) {
                        self::addResourceToGraph($value, $graph); 
                       }
                } else {
                    //var_dump($value);
                    throw new InvalidArgumentException(
                        "Unexpected value found for property {$propName} " . var_export($value)
                    );
                }
            }
        }
    }
}
