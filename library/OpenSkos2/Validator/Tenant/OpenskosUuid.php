<?php

namespace OpenSkos2\Validator\Tenant;

use OpenSkos2\Tenant as Tenant;
use OpenSkos2\Validator\AbstractTenantValidator;

class OpenskosUuid extends AbstractTenantValidator
{
    protected function validateTenant(Tenant $resource)
    {
        return parent::genericValidate('\CommonProperties\Uuid::validate', $resource);
    }
}
