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
use OpenSkos2\Namespaces\Rdf;
use OpenSkos2\Namespaces\VCard;
use OpenSkos2\Set;
use OpenSkos2\SetCollection;
use OpenSkos2\SkosXl\Label;
use OpenSkos2\SkosXl\LabelCollection;
use OpenSkos2\Concept;
use OpenSkos2\ConceptCollection;
use OpenSkos2\ConceptScheme;
use OpenSkos2\ConceptSchemeCollection;
use OpenSkos2\SkosCollection;
use OpenSkos2\SkosCollectionCollection;
use OpenSkos2\Tenant;
use OpenSkos2\TenantCollection;
use OpenSkos2\RelationType;
use OpenSkos2\RelationTypeCollection;
use OpenSkos2\Person;
use OpenSkos2\Exception\InvalidArgumentException;
use OpenSkos2\Rdf\Literal;
use OpenSkos2\Rdf\Resource;
use OpenSkos2\Rdf\ResourceCollection;
use OpenSkos2\Rdf\Uri;

class EasyRdf
{

    private static $allowedSubresources = [VCard::ORG, VCard::ADR];

    /**
     * @param \EasyRdf\Graph $graph to $read
     * @param string $expectedType If expected type is set, a collection of that type will be enforced.
     * @param array $allowedChildrenTypes , optional For example skos xl
     * @return ResourceCollection
     */
    public static function graphToResourceCollection(Graph $graph, $expectedType = null, $allowedChildrenTypes = [])
    {
        $collection = self::createResourceCollection($expectedType);
        $alreadyAddedAsChild = [];

        foreach ($graph->resources() as $resource) {
            if (isset($alreadyAddedAsChild[$resource->getUri()])) {
                // We skip resources which are part of other resource
                continue;
            }

           
            $openskosResource = self::toOpenskosResource($resource, $allowedChildrenTypes, $alreadyAddedAsChild);
            if ($openskosResource === false) {
                // Filter out resources which are not fully described.
                continue;
            }
            $collection[] = $openskosResource;
        }

        return $collection;
    }

    protected static function toOpenskosResource($resource, $allowedChildrenTypes, &$alreadyAddedAsChild)
    {
        /** @var $resource \EasyRdf\Resource */
        $type = $resource->get('rdf:type');
                
        // Filter out resources which are not fully described.
        if (!$type) {
            return false;
        }

        $openskosResource = self::createResource(
            $resource->getUri(),
            $type
        );
        
        foreach ($resource->propertyUris() as $propertyUri) {
            // We already have the rdf type proprty from the resource creation. No need to put it again.
            if ($propertyUri === Rdf::TYPE && $openskosResource->hasProperty(Rdf::TYPE)) {
                continue;
            }

            foreach ($resource->all(new \EasyRdf\Resource($propertyUri)) as $propertyValue) {
                if ($propertyValue instanceof \EasyRdf\Literal) {
                    $openskosResource->addProperty(
                        $propertyUri,
                        new Literal(
                            $propertyValue->getValue(),
                            $propertyValue->getLang(),
                            $propertyValue->getDatatypeUri()
                        )
                    );
                } elseif ($propertyValue instanceof \EasyRdf\Resource) {
                    if ($propertyValue->isBNode()) {
                        if (in_array($propertyUri, self::$allowedSubresources)) {
                            $subResource = self::toOpenskosSubResource($propertyValue);
                            $openskosResource->addProperty($propertyUri, $subResource);
                            continue;
                        } else {
                            continue;
                        }
                    }

                    // Check if it is an allowed fully described child resource.
                    if (in_array($propertyUri, $allowedChildrenTypes)) {
                        $childResource = self::toOpenskosResource($propertyValue, [], $alreadyAddedAsChild);
                        if ($childResource !== false) {
                            $alreadyAddedAsChild[$childResource->getUri()] = true;
                            $openskosResource->addProperty($propertyUri, $childResource);
                            continue;
                        }
                    }

                    // Not a fully described resource or not a subresource so we just add the uri.
                    $openskosResource->addProperty($propertyUri, new Uri($propertyValue->getUri()));
                }
            }
        }

        return $openskosResource;
    }

    protected static function toOpenskosSubResource($resource)
    {
        $openskosResource = new Resource($resource->getUri());

        foreach ($resource->propertyUris() as $propertyUri) {
            // We already have the rdf type proprty from the resource creation. No need to put it again.
            if ($propertyUri === Rdf::TYPE && $openskosResource->hasProperty(Rdf::TYPE)) {
                throw new InvalidArgumentException(
                    "Unexpected value found for property {$resource->getUri} is a subresource and should not have type. "
                );
            }

            foreach ($resource->all(new \EasyRdf\Resource($propertyUri)) as $propertyValue) {
                if ($propertyValue instanceof \EasyRdf\Literal) {
                    $openskosResource->addProperty(
                        $propertyUri,
                        new Literal(
                            $propertyValue->getValue(),
                            $propertyValue->getLang(),
                            $propertyValue->getDatatypeUri()
                        )
                    );
                } elseif ($propertyValue instanceof \EasyRdf\Uri) {
                    $openskosResource->addProperty($propertyUri, new Uri($propertyValue->getUri()));
                }
            }
        }

        return $openskosResource;
    }

    /**
     * @param Resource $resource
     * @return Graph
     */
    public static function resourceToGraph(Resource $resource)
    {
        $graph = new Graph();
        self::fromOpenSkosResource($resource, $graph);
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
            self::fromOpenSkosResource($resource, $graph);
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
            switch ($type) {
                case Concept::TYPE:
                    return new Concept($uri);
                case ConceptScheme::TYPE:
                    return new ConceptScheme($uri);
                case SkosCollection::TYPE:
                    return new SkosCollection($uri);
                case Set::TYPE:
                    return new Set($uri);
                case Tenant::TYPE:
                    return new Tenant($uri);
                case RelationType::TYPE:
                    return new RelationType($uri);
                case Person::TYPE:
                    return new Person($uri);
                case Label::TYPE:
                    return new Label($uri);
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
        switch ($type) {
            case Concept::TYPE:
                return new ConceptCollection();
            case ConceptScheme::TYPE:
                return new ConceptSchemeCollection();
            case SkosCollection::TYPE:
                return new SkosCollectionCollection();
            case Set::TYPE:
                return new SetCollection();
            case Tenant::TYPE:
                return new TenantCollection();
            case RelationType::TYPE:
                return new RelationTypeCollection();
            case Label::TYPE:
                return new LabelCollection();
            default:
                return new ResourceCollection();
        }
    }

    /**
     * @param \OpenSkos2\Rdf\Resource $resource
     * @param \EasyRdf\Graph $graph
     * @return \EasyRdf\Resource
     * @throws InvalidArgumentException
     */
    protected static function fromOpenSkosResource(Resource $resource, \EasyRdf\Graph $graph)
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
                } elseif ($value instanceof Resource) {
                    $easyResource->addResource($propName, self::fromOpenSkosResource($value, $graph));
                } elseif ($value instanceof Uri) {
                    $easyResource->addResource($propName, trim($value->getUri()));
                } else {
                    throw new InvalidArgumentException(
                        "Unexpected value found for property {$propName} " . var_export($value)
                    );
                }
            }
        }

        return $easyResource;
    }
}
