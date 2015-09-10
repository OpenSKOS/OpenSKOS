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

/**
 * Representation of tenant.
 */
class Tenant
{
    /**
     * @var string
     */
    protected $code;
    
    /**
     * @var bool
     */
    protected $isNotationUniquePerTenant;
    
    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Is the notation required to be unique per tenant, not per scheme.
     * @return bool
     */
    public function isNotationUniquePerTenant()
    {
        return $this->isNotationUniquePerTenant;
    }

    /**
     * @param string $code
     * @param bool $isNotationUniquePerTenant
     */
    public function __construct($code, $isNotationUniquePerTenant = false)
    {
        $this->code = $code;
        $this->isNotationUniquePerTenant = $isNotationUniquePerTenant;
    }
}
