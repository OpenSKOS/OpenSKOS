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
use OpenSkos2\Namespaces\SkosXl;
use \EasyRdf\RdfNamespace;

class Namespaces
{
    /**
     * List of some additional namespaces used in the library.
     * @var array
     */
    protected static $additionalNamespaces = [
        'openskos' => OpenSkos::NAME_SPACE,
        'skosxl' => SkosXl::NAME_SPACE,
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
}
