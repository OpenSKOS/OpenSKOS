<?php

namespace OpenSkos2\Validator\Tenant;

use OpenSkos2\Tenant as Tenant;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Validator\AbstractTenantValidator;

class OpenskosEnableStatussesSystem extends AbstractTenantValidator
{
    protected function validateTenant(Tenant $resource)
    {
        return $this->validateProperty($resource, OpenSkos::ENABLESTATUSSESSYSTEMS, true, true, true, false);
    }
}
