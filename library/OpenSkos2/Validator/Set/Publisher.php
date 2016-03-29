<?php

namespace OpenSkos2\Validator\Set;

use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Set;
use OpenSkos2\Validator\AbstractSetValidator;

class Publisher extends AbstractSetValidator
{
    
    protected function validateSet(Set $resource)
    {
        // check if this corresponds to the logged-in tenant
        return parent::genericValidate('\CommonProperties\UniqueOptionalProperty::validate', $resource, DcTerms::PUBLISHER, false, $this->getErrorMessages());
    
    }
}
