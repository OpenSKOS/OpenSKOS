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

namespace OpenSkos2\Validator\DependencyAware;

use OpenSkos2\Tenant;

trait TenantAwareTrait
{
    /**
     * @var Tenant
     */
    protected $tenant;
    
    /**
     * @param Tenant $tenant
     * @return self
     */
    public function setTenant(Tenant $tenant)
    {
        $this->tenant = $tenant;
        return $this;
    }
    
    /**
     * @return Tenant
     */
    public function getTenant()
    {
        if (empty($this->tenant)) {
            throw new \RuntimeException(
                'The class has a dependency on OpenSkos2\Tenant which is not fullfilled.'
            );
        }
        
        return $this->tenant;
    }
}
