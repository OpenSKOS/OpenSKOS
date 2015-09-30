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
     * Output the literal as string.
     * @return string
     */
    public function __toString()
    {
        // We don't show language or type in the to string.
        // Not needed on the places where we use it.
        return $this->getValue();
    }
}
