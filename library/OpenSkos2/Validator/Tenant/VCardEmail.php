<?php

namespace OpenSkos2\Validator\Tenant;

use OpenSkos2\Tenant;
use OpenSkos2\Namespaces\VCard;
use OpenSkos2\Validator\AbstractTenantValidator;

class VCardEmail extends AbstractTenantValidator
{

    protected function validateTenant(Tenant $resource)
    {
        return $this->validateProperty($resource, VCard::EMAIL, true, true, false, true);
    }
}
