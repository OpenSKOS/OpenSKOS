<?php

namespace OpenSkos2\Validator\Tenant;

use OpenSkos2\Validator\AbstractTenantValidator;
use OpenSkos2\Tenant as Tenant;

class OpenskosCode extends AbstractTenantValidator
{
    protected function validateTenant(Tenant $resource)
    {
        return $this->validateOpenskosCode($resource);
    }
}
