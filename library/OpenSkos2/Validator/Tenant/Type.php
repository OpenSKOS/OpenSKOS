<?php

namespace OpenSkos2\Validator\Tenant;

use OpenSkos2\Tenant as Tenant;
use OpenSkos2\Validator\AbstractTenantValidator;
use OpenSkos2\Validator\GenericProperties\Type as GenericType;

class Type extends AbstractTenantValidator
{
    
    protected function validateTenant(Tenant $resource)
    {
       $this->errorMessages = GenericType::validate($resource, $this->forUpdate);
       return (count($this->errorMessages) === 0);
    }
}