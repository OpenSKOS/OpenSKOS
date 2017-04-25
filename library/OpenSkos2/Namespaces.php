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

namespace OpenSkos2;

use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Namespaces\Dc;
use \EasyRdf\RdfNamespace;

class Namespaces
{

    /**
     * List of some additional namespaces used in the library.
     * @var array
     */
    protected static $additionalNamespaces = [
        'openskos' => OpenSkos::NAME_SPACE,
//         Temporary disable skos xl
//        'skosxl' => SkosXl::NAME_SPACE,
        'dc' => Dc::NAME_SPACE, // Very important for distinguishing dcterms and dc prefixes.
    ];

    /**
     * Gets list of additional namespaces which are not commonly used. (not used in EasyRdf)
     * @return array [prefix => namespace]
     */
    public static function getAdditionalNamespaces()
    {
        return self::$additionalNamespaces;
    }

    /**
     * Gets list of namespaces normally used in concept's rdf format.
     * @return array [prefix => namespace]
     */
    public static function getRdfConceptNamespaces()
    {
        // @TODO Check ResourceManager->fetchNamespaces().
        // It is what we need but does not work always.
        return [
            'rdf' => \OpenSkos2\Namespaces\Rdf::NAME_SPACE,
            'dc' => \OpenSkos2\Namespaces\Dc::NAME_SPACE,
            'dcterms' => \OpenSkos2\Namespaces\DcTerms::NAME_SPACE,
            'skos' => \OpenSkos2\Namespaces\Skos::NAME_SPACE,
//            Temporary disable skos xl
//            'skosxl' => \OpenSkos2\Namespaces\SkosXl::NAME_SPACE,
            'openskos' => \OpenSkos2\Namespaces\OpenSkos::NAME_SPACE,
        ];
    }

    /**
     * Makes http://openskos.org/xmlns#status to be openskos:status
     * @param string $property
     * @return string
     */
    public static function shortenProperty($property)
    {
        foreach (self::$additionalNamespaces as $prefix => $uri) {
            \EasyRdf\RdfNamespace::set($prefix, $uri);
        }

        $shortName = RdfNamespace::shorten($property);
        if (empty($shortName)) {
            return $property;
        } else {
            return $shortName;
        }
    }

    /**
     * Makes openskos:status to be http://openskos.org/xmlns#status
     * If not possible - return the same string
     * @param string $shortProperty
     * @return string
     */
    public static function expandProperty($shortProperty)
    {
        foreach (self::$additionalNamespaces as $prefix => $uri) {
            \EasyRdf\RdfNamespace::set($prefix, $uri);
        }

        return RdfNamespace::expand($shortProperty);
    }

    public static function mapRdfTypeToClassName($type)
    {
        if ($type) {
            switch ($type) {
                case Concept::TYPE:
                    return "\OpenSkos2\Concept";
                case \OpenSkos2\ConceptScheme::TYPE:
                    return "\OpenSkos2\ConceptScheme";
                case Set::TYPE:
                    return "\OpenSkos2\Set";
                case Person::TYPE:
                    return "\OpenSkos2\Person";
                case Tenant::TYPE:
                    return "\OpenSkos2\Tenant";
                case SkosCollection::TYPE:
                    return "\OpenSkos2\SkosCollection";
                default:
                    return "\OpenSkos2\Rdf\Resource";
            }
        } else {
            return "\OpenSkos2\Rdf\Resource";
        }
    }
}
