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

namespace OpenSkos2\Validator;

use OpenSkos2\Tenant as Tenant;
use OpenSkos2\Rdf\Resource as RdfResource;

abstract class AbstractTenantValidator extends AbstractResourceValidator
{
    /**
     * @param RdfResource $resource
     * @return bool
     */
    public function validate(RdfResource $resource)
    {
        
        if ($resource instanceof Tenant) {
             $this->errorMessages = array_merge($this->errorMessages, $this->validateTenant($resource));
            return (count($this->errorMessages) ===0);
        }
        return false;
    }

   
    abstract protected function validateTenant(Tenant $tenant);
}
