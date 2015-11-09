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

use \EasyRdf\RdfNamespace;

class Namespaces
{
    /**
     * List of some additional namespaces used in the library.
     * @var array
     */
    protected static $additionalNamespaces = [
        'openskos' => 'http://openskos.org/xmlns#',
    ];
    
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
     * Makes http://openskos.org/xmlns#status to be status
     * @param string $property
     * @return string
     */
    public static function shortenPropertyNameOnly($property)
    {
        foreach (self::$additionalNamespaces as $prefix => $uri) {
            \EasyRdf\RdfNamespace::set($prefix, $uri);
        }
        
        $uriParts = RdfNamespace::splitUri($property);
        if (empty($uriParts[1])) {
            return $property;
        } else {
            return $uriParts[1];
        }
    }
}
