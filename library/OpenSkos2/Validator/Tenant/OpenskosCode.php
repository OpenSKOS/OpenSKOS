<?php

namespace OpenSkos2\Validator\Tenant;

use OpenSkos2\Validator\AbstractTenantValidator;
use OpenSkos2\Tenant as Tenant;
use OpenSkos2\Validator\GenericProperties\OpenskosCode as GenericOpensskosCode;

class OpenskosCode extends AbstractTenantValidator
{
    protected function validateTenant(Tenant $resource)
    {
       $this->errorMessages = GenericOpenskosCode::validate($resource, $this->forUpdate);
       return (count($this->errorMessages) === 0);
    }
}
