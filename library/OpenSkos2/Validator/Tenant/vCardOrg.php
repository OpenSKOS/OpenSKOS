<?php

namespace OpenSkos2\Validator\Tenant;

use OpenSkos2\Tenant as Tenant;
use OpenSkos2\Namespaces\vCard;
use OpenSkos2\Validator\AbstractTenantValidator;
class vCardOrg extends AbstractTenantValidator
{
    
    protected function validateTenant(Tenant $resource) {
        $orgCheck = parent::genericValidate('\CommonProperties\UniqueObligatoryProperty::validate', $resource, vCard::ORG, false);
        if ($orgCheck) {
            $org = $resource->getProperty(vCard::ORG);
            return parent::genericValidate('\CommonProperties\UniqueObligatoryProperty::validate', $org[0], vCard::ORGNAME, false);
        } else {
            return false;
        }
    }

}