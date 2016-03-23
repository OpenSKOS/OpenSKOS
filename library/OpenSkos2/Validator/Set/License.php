<?php

namespace OpenSkos2\Validator\Set;

use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Validator\AbstractSetValidator;
use OpenSkos2\Set;

class License extends AbstractSetValidator
{
    
    protected function validateSet(Set $resource)
    {
        return parent::genericValidate('\CommonProperties\UniqueOptionalProperty::validate', $resource, DcTerms::LICENSE, false, $this->getErrorMessages());
    }
}
