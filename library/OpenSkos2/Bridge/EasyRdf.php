<?php

namespace OpenSkos2\Bridge;

use EasyRdf_Graph;
use EasyRdf_Literal;
use EasyRdf_Resource;
use OpenSkos2\Rdf\Literal;
use OpenSkos2\Rdf\Resource;
use OpenSkos2\Concept;
use OpenSkos2\Schema;
use OpenSkos2\Collection;
use OpenSkos2\Rdf\ResourceCollection;
use OpenSkos2\ConceptCollection;
use OpenSkos2\SchemaCollection;
use OpenSkos2\CollectionCollection;
use OpenSkos2\Rdf\Uri;

/**
 * Created by PhpStorm.
 * User: jsmit
 * Date: 27/08/2015
 * Time: 15:59
 */
class EasyRdf
{
    /**
     * @param EasyRdf_Graph $graph
     * @return ResourceCollection
     */
    public static function graphToResourceCollection(EasyRdf_Graph $graph)
    {
        $myResources = new ResourceCollection();
        foreach ($graph->resources() as $resource) {
            /** @var $resource EasyRdf_Resource */
            $type = $resource->get('rdf:type');

            if (!$type) {
                continue;
            }

            $myResource = self::factory($type->getUri(), $resource->getUri());

            foreach ($resource->propertyUris() as $propertyUri) {

                foreach ($resource->all(new EasyRdf_Resource($propertyUri)) as $propertyValue) {
                    if ($propertyValue instanceof EasyRdf_Literal) {
                        $myResource->addProperty($propertyUri,
                            new Literal($propertyValue->getValue(), $propertyValue->getLang()));
                    } elseif ($propertyValue instanceof EasyRdf_Resource) {
                        $myResource->addProperty($propertyUri, new Uri($propertyValue->getUri()));
                    }
                }
            }

            $myResources [] = $myResource;
        }
        return $myResources;
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
     * 
     * @param string $type
     * @param string $uri
     * @return \OpenSkos2\Bridge\Schema|\OpenSkos2\Bridge\Concept|\OpenSkos2\Bridge\Collection|Resource
     */
    public static function factory($type, $uri = null)
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
}
