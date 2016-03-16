<?php

namespace OpenSkos2\Validator\Tenant;

use OpenSkos2\Tenant as Tenant;
use OpenSkos2\Validator\CommonProperties;
use OpenSkos2\Namespaces\vCard;
use OpenSkos2\Validator\AbstractTenantValidator;

class vCardEmail extends AbstractTenantValidator
{
    
    protected function validateTenant(Tenant $resource)
    {
        return parent::genericValidate('\CommonProperties\UniqueObligatoryProperty::validate', $resource, vCard::EMAIL, false);
        
    }
}