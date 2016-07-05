<?php

namespace OpenSkos2\Validator\Tenant;

use OpenSkos2\Tenant;
use OpenSkos2\Namespaces\vCard;
use OpenSkos2\Validator\AbstractTenantValidator;

class vCardEmail extends AbstractTenantValidator
{
    
    protected function validateTenant(Tenant $resource)
    {
       return $this->validateProperty($resource, vCard::EMAIL, true, true, true, false, true);
    }
}