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

use EasyRdf_Graph;
use EasyRdf_Literal;
use EasyRdf_Resource;
use OpenSkos2\Collection;
use OpenSkos2\Concept;
use OpenSkos2\Rdf\Literal;
use OpenSkos2\Rdf\Resource;
use OpenSkos2\Rdf\ResourceCollection;
use OpenSkos2\Rdf\Uri;
use OpenSkos2\Schema;
use OpenSkos2\ConceptCollection;
use OpenSkos2\SchemaCollection;
use OpenSkos2\CollectionCollection;

class EasyRdf
{
    /**
     * @param EasyRdf_Graph $graph to $read
     * @param string $expectedType If expected type is set, a collection of that type will be enforced.
     * @return ResourceCollection
     */
    public static function graphToResourceCollection(EasyRdf_Graph $graph, $expectedType = null)
    {
        $collection = self::createResourceCollection($expectedType);

        foreach ($graph->resources() as $resource) {
            /** @var $resource EasyRdf_Resource */
            $type = $resource->get('rdf:type');

            if (!$type) {
                continue;
            }

            $myResource = self::createResource($type->getUri(), $resource->getUri());

            foreach ($resource->propertyUris() as $propertyUri) {

                foreach ($resource->all(new EasyRdf_Resource($propertyUri)) as $propertyValue) {
                    if ($propertyValue instanceof EasyRdf_Literal) {
                        $myResource->addProperty(
                            $propertyUri,
                            new Literal($propertyValue->getValue(), $propertyValue->getLang())
                        );
                    } elseif ($propertyValue instanceof EasyRdf_Resource) {
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
     * @return EasyRdf_Graph
     */
    public static function resourceToGraph(Resource $resource)
    {
        $easyResource = new \EasyRdf_Resource($resource->getUri(), new \EasyRdf_Graph());
        foreach ($resource->getProperties() as $propName => $property) {
            foreach ($property as $value) {
                /**
                 * @var $value Object
                 */
                if ($value instanceof Literal) {
                    $easyResource->addLiteral($propName, $value->getValue(), $value->getLanguage());
                } else {
                    $easyResource->addResource($propName, $value->getValue());
                }
            }
        }

        $graph = $easyResource->getGraph();

        return $graph;
    }
    
    /**
     * Creates a resource matching the give type.
     * @param string $type
     * @param string $uri
     * @return Resource
     */
    public static function createResource($type, $uri = null)
    {
        switch ($type) {
            case Concept::TYPE:
                return new Concept($uri);
            case Schema::TYPE:
                return new Schema($uri);
            case Collection::TYPE:
                return new Collection($uri);
            default:
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
            case Schema::TYPE:
                return new SchemaCollection();
            case Collection::TYPE:
                return new CollectionCollection();
            default:
                return new ResourceCollection();
        }
    }
}
