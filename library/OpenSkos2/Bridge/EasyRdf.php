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
use OpenSkos2\RelationType;
use OpenSkos2\RelationTypeCollection;
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
    $collection = self::createResourceCollection($expectedType);
    if ($expectedType !== null) {
      if ($expectedType instanceof Uri) {
        $expectedTypeUri = $expectedType->getUri();
      } else {
        $expectedTypeUri = $expectedType;
      }
    } else {
      $expectedTypeUri = null;
    }
    /** @var $resource \EasyRdf\Resource */
    foreach ($graph->resources() as $resource) {
      $type = self::getRdfType($resource);
      if ($type !== null) {
        if ($expectedTypeUri === null || ($type === $expectedTypeUri)) {
          $myResource = self::createResource($resource->getUri(), $type);
          self::makeNode($myResource, $resource);
          $collection[] = $myResource;
        }
      }
    }
    return $collection;
  }

  private static function makeNode(&$myResource, $resource) {
    $propertyUris = $resource->propertyUris();
    // type has been already identified and set in the resource constructor
    // we do no need to duplicate it 
    $propertyUris = array_diff($propertyUris, array(Rdf::TYPE));
    foreach ($propertyUris as $propertyUri) {
      foreach ($resource->all(new Resource2($propertyUri)) as $propertyValue) {
        if ($propertyValue instanceof Literal2) {
          $myResource->addProperty(
            $propertyUri, new Literal($propertyValue->getValue(), $propertyValue->getLang(), $propertyValue->getDatatypeUri())
          );
        } elseif ($propertyValue instanceof Resource2) {
          // recursion
          if ((self::getRdfType($propertyValue)) === null) { //a proper subresource, we must/can iterate on it, it does not have proper handles
            $mySubResource = self::createResource($propertyValue->getUri(), null);
            self::makeNode($mySubResource, $propertyValue);
            $myResource->addProperty($propertyUri, $mySubResource);
          } else {
            //  it is an uri
            $myResource->addProperty($propertyUri, new Uri($propertyValue->getUri()));
          }
        }
      }
    }
  }

  /**
   * @param Resource $resource
   * @return Graph
   */
  public static function resourceToGraph(Resource $resource) {
    $graph = new Graph();
    self::addResourceToGraph($resource, $graph);
    return $graph;
  }

  /**
   * @param ResourceCollection $collection
   * @return Graph
   */
  public static function resourceCollectionToGraph(ResourceCollection $collection) {
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
  protected static function createResource($uri, $type) {

    if ($type) {
      switch ($type) {
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
        case RelationType::TYPE:
          return new RelationType($uri);
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
  public static function createResourceCollection($type) {

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
      case RelationType::TYPE:
        return new RelationTypeCollection();
      default:
        return new ResourceCollection();
    }
  }

  /**
   * @param Resource $resource
   * @param Graph $graph
   * @throws InvalidArgumentException
   */
  protected static function addResourceToGraph(Resource $resource, Graph $graph) {
    $easyResource = new Resource2($resource->getUri(), $graph);
    foreach ($resource->getProperties() as $propName => $property) {
      foreach ($property as $value) {
        /**
         * @var $value Object
         */
        if ($value instanceof Literal) {
          $val = $value->getValue();
          $literalType = $value->getType();
          if ($literalType != Literal::TYPE_BOOL && ($value == 'true' || $value == 'false')) {
            $literalType = Literal::TYPE_BOOL;
          }
          // Convert timestamp to string
          if ($val instanceof DateTime) {
            $val = $val->format(\DATE_W3C);
          }

          $easyResource->addLiteral(
            $propName, new Literal2($val, $value->getLanguage(), $literalType)
          );
        } else if ($value instanceof Uri) {
          if ($value instanceof Resource) {
            $easyResource->addResource($propName, trim($value->getUri()));
            self::addResourceToGraph($value, $graph);
          } else {
            $uris = $value->getUri();
            if (is_array($uris)) {
              foreach ($uris as $uri) {
                $easyResource->addResource($propName, trim($uri));
              }
            } else {
              $easyResource->addResource($propName, trim($uris));
            }
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

  private static function getRdfType($resource) {
    $rawType = $resource->get('rdf:type');
    if ($rawType !== null) {
      if ($rawType instanceof Resource2) {
        return $rawType->getUri();
      } else {
        throw new InvalidArgumentException('Xml parsing error. Possible reason: complex elements must be augmented with rdf:parseType="Resource", e.g. <vcard:ORG rdf:parseType="Resource">
      <vcard:orgname>CLARIN</vcard:orgname>
            </vcard:ORG>');
      }
    } else {
      return null;
    }
  }

}
