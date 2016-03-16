<?php

namespace OpenSkos2\Validator\Tenant;

use OpenSkos2\Tenant as Tenant;
use OpenSkos2\Validator\CommonProperties;
use OpenSkos2\Validator\AbstractTenantValidator;

use OpenSkos2\Namespaces\Openskos;

class OpenskosEnableStatussesSystem extends AbstractTenantValidator
{
    protected function validateTenant(Tenant $resource)
    {
        return parent::genericValidate('\CommonProperties\UniqueObligatoryProperty::validate', $resource, Openskos::ENABLESTATUSSESSYSTEMS, true);
    }
}
