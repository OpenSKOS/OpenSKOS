<?php

namespace OpenSkos2\Validator\Tenant;

use OpenSkos2\Tenant as Tenant;
use OpenSkos2\Validator\AbstractTenantValidator;
use OpenSkos2\Validator\GenericProperties\Uuid;

class OpenskosUuid extends AbstractTenantValidator
{
    protected function validateTenant(Tenant $resource)
    {
       $this->errorMessages = Uuid::validate($resource, $this->forUpdate);
       return (count($this->errorMessages) === 0);
    }
}
