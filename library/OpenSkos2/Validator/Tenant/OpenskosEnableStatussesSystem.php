<?php

namespace OpenSkos2\Validator\Tenant;

use OpenSkos2\Tenant as Tenant;
use OpenSkos2\Validator\GenericProperties\EnableStatussesSystem;
use OpenSkos2\Validator\AbstractTenantValidator;

class OpenskosEnableStatussesSystem extends AbstractTenantValidator
{
    protected function validateTenant(Tenant $resource)
    {
       $this->errorMessages = EnableStatussesSystem::validate($resource);
       return (count($this->errorMessages) === 0); }
}
