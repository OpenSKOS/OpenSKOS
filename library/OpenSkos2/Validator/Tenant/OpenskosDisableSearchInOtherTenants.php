<?php

namespace OpenSkos2\Validator\Tenant;

use OpenSkos2\Tenant as Tenant;
use OpenSkos2\Validator\AbstractTenantValidator;
use OpenSkos2\Namespaces\Openskos;

class OpenskosDisableSearchInOtherTenants extends AbstractTenantValidator
{
    protected function validateTenant(Tenant $resource)
    {
        return parent::genericValidate('\CommonProperties\UniqueObligatoryProperty::validate', $resource, Openskos::DISABLESEARCHINOTERTENANTS, true);
       
    }
}
