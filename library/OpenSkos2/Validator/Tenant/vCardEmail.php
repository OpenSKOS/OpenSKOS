<?php

namespace OpenSkos2\Validator\Tenant;

use OpenSkos2\Tenant as Tenant;
use OpenSkos2\Validator\GenericProperties\Email;
use OpenSkos2\Validator\AbstractTenantValidator;

class vCardEmail extends AbstractTenantValidator
{
    
    protected function validateTenant(Tenant $resource)
    {
       $this->errorMessages = Email::validate($resource, $this->isForUpdate);
       return (count($this->errorMessages) === 0);
    }
}