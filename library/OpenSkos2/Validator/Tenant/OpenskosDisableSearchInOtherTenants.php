<?php

namespace OpenSkos2\Validator\Tenant;

use OpenSkos2\Tenant as Tenant;
use OpenSkos2\Validator\AbstractTenantValidator;
use OpenSkos2\Validator\GenericProperties\DisableSearchInOtherTenants;

class OpenskosDisableSearchInOtherTenants extends AbstractTenantValidator
{
    protected function validateTenant(Tenant $resource)
    {
       $this->errorMessages = DisableSearchInOtherTenants::validate($resource);
       return (count($this->errorMessages) === 0);
    }
}
