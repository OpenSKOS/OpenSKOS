<?php

namespace OpenSkos2\Validator\Tenant;

use OpenSkos2\Tenant as Tenant;
use OpenSkos2\Validator\CommonProperties;
use OpenSkos2\Validator\AbstractTenantValidator;
class Type extends AbstractTenantValidator
{
    
    protected function validateTenant(Tenant $resource)
    {
        return parent::genericValidate('\CommonProperties\Type::validate', $resource);
    }
}