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

use OpenSkos2\Exception\InvalidResourceException;

class Literal implements Object
{

    const TYPE_DATETIME = "http://www.w3.org/2001/XMLSchema#dateTime";
    const TYPE_BOOL = "http://www.w3.org/2001/XMLSchema#bool";

    /**
     * @var string
     */
    protected $language;

    /**
     * @var string
     */
    protected $value;

    /**
     * @var string
     */
    private $type;

    /**
     * Literal constructor.
     * @param string $value
     * @param string $language
     * @param string $type
     * @throws InvalidResourceException
     */
    public function __construct($value, $language = null, $type = null)
    {
        if (is_array($value)) {
            throw new InvalidResourceException("Value cannot be an array");
        }
        $this->value = $value;
        $this->language = $language;
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @param string $language
     * @return $this
     */
    public function setLanguage($language)
    {
        $this->language = $language;
        return $this;
    }

    /*
     * @return string
     */

    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return self
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Output the literal as string.
     * @return string
     */
    public function __toString()
    {
        // We don't show language or type in the to string.
        // Not needed on the places where we use it.
        if ($this->getValue() instanceof \DateTime) {
            return $this->getValue()->format('c');
        } else {
            return (string) $this->getValue();
        }
    }
    
    /**
     * Is the literal empty.
     * @return type
     */
    public function isEmpty()
    {
        return $this->value === null || $this->value === '';
    }
    
    /**
     * Finds if the current literal is in the given array.
     * @param Literal[] $literalsArray
     * @return boolean
     */
    public function isInArray(array $literalsArray)
    {
        foreach ($literalsArray as $literalLookup) {
            if ($this->isEqual($literalLookup)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * @param Literal $literalToCompare
     * @return boolean
     */
    public function isEqual(Literal $literalToCompare)
    {
        if ($literalToCompare->getValue() == $this->getValue()
         && $literalToCompare->getLanguage() == $this->getLanguage()
         && $literalToCompare->getType() == $this->getType()) {
            return true;
        }
    }
}
