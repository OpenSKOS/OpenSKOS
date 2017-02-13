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

class Uri implements Object, ResourceIdentifier
{
    /**
     * @var string
     */
    protected $uri;

    /**
     * Literal constructor.
     * @param string $value
     */
    public function __construct($value)
    {
        // We allow generated (by easy rdf) uris which are not valid uri.
        $isGeneratedUri = stripos($value, '_:genid') === 0;
        
        // Null values where allowed from the start some functionality depends on it like createing new graphs :(
        if (!$isGeneratedUri && $value !== null && !filter_var($value, FILTER_VALIDATE_URL)) {
            throw new Exception\InvalidUriException('Invalid URI: ' . $value);
        }

        $this->uri = $value;
    }

    /**
     * Output the uri as string.
     * @return string
     */
    public function __toString()
    {
        return $this->uri;
    }

    public function getUri()
    {
        return $this->uri;
    }
}
