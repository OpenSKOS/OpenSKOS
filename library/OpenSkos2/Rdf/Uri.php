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
        $this->uri = $value;
    }


    /**
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }
}