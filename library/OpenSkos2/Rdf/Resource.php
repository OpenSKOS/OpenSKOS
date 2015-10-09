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

class Resource extends Uri implements ResourceIdentifier
{
    protected $properties = [];

    /**
     * @return array of RdfObject[]
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @param string $predicate
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
     * @param string $predicate
     * @param RdfObject $value
     */
    public function addProperty($predicate, RdfObject $value)
    {
        $this->properties[$predicate][] = $value;
        return $this;
    }
    
    /**
     * Make sure the property is only added once
     *
     * @param string $predicate
     * @param RdfObject $value
     * @return \OpenSkos2\Rdf\Resource
     */
    public function addUniqueProperty($predicate, RdfObject $value)
    {
        if (!isset($this->properties[$predicate])) {
            $this->properties[$predicate][] = $value;
            return $this;
        }
        foreach ($this->properties[$predicate] as $obj) {
            if ($obj->getValue() === $value->getValue() && $obj->getLanguage() === $value->getLanguage()) {
                return $this;
            }
        }
        $this->properties[$predicate][] = $value;
        return $this;
    }

    /**
     * @param string $predicate
     * @param RdfObject $value
     * @return $this
     */
    public function setProperty($predicate, RdfObject $value)
    {
        $this->properties[$predicate] = [$value];
        return $this;
    }
    
    /**
     * Set multiple values at once, override existing values
     *
     * @param string $predicate
     * @param RdfObject[] $values
     */
    public function setProperties($predicate, array $values)
    {
        $this->properties[$predicate] = $values;
    }

    /**
     * @param string $predicate
     */
    public function unsetProperty($predicate)
    {
        unset($this->properties[$predicate]);
        return $this;
    }

    /**
     * @param string $predicate
     * @return bool
     */
    public function hasProperty($predicate)
    {
        return isset($this->properties[$predicate]);
    }
    
    /**
     * @param string $predicate
     * @return bool
     */
    public function isPropertyEmpty($predicate)
    {
        return empty($this->properties[$predicate]);
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
        return current($this->getProperty(\OpenSkos2\Namespaces\Rdf::TYPE));
    }
    
    /**
     * Is the current resource a blank node.
     * It is if no uri given or generated uri starting with _:
     * @return boolean
     */
    public function isBlankNode()
    {
        return empty($this->uri) || preg_match('/^_:/', $this->uri);
    }
    
    /**
     * Gets the specified property values but filter only those in the specified language.     *
     * @param string $predicate
     * @param string $language
     * @return RdfObject[]
     */
    public function retrievePropertyInLanguage($predicate, $language)
    {
        $values = [];
        foreach ($this->getProperty($predicate) as $value) {
            if ($value instanceof Literal && $value->getLanguage() == $language) {
                $values[] = $value;
            }
        }
        return $values;
    }

    /**
     * Gets list of all languages that currently exist in the properties of the resource.
     * @return string[]
     */
    public function retrieveLanguages()
    {
        $languages = [];
        foreach ($this->getProperties() as $property) {
            foreach ($property as $value) {
                if ($value instanceof Literal
                        && $value->getLanguage() !== null
                        && !isset($languages[$value->getLanguage()])) {
                    $languages[$value->getLanguage()] = true;
                }
            }
        }
        
        return array_keys($languages);
    }
}
