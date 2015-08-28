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

use OpenSkos2\Rdf\Object as RdfObject;

class Resource
{
    const PROPERTY_RDF_TYPE = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type';

    protected $properties = [];

    /**
     * @var string
     */
    private $uri;

    /**
     * Resource constructor.
     * @param string $uri
     */
    public function __construct($uri = null)
    {
        $this->uri = $uri;
    }

    /**
     * @return array of RdfObject[]
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @param $predicate
     * @return RdfObject[]
     */
    public function getProperty($predicate)
    {
        if (!isset($this->properties[$predicate])) {
            return [];
        } else {
            return $this->properties[$predicate];
        }
    }

    /**
     * @param $propertyName
     * @param RdfObject $value
     */
    public function addProperty($propertyName, RdfObject $value)
    {
        $this->properties[$propertyName][] = $value;
    }

    /**
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @param string $uri
     */
    public function setUri($uri)
    {
        $this->uri = $uri;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return current($this->getProperty(self::PROPERTY_RDF_TYPE));
    }
}
