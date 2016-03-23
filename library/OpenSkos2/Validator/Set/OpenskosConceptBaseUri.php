<?php

namespace OpenSkos2\Validator\Set;

use OpenSkos2\Namespaces\Openskos;
use OpenSkos2\Set;
use OpenSkos2\Validator\AbstractSetValidator;

class OpenskosConceptBaseUri extends AbstractSetValidator
{
    protected function validateSet(Set $resource)
    {
        return parent::genericValidate('\CommonProperties\UniqueOptionalProperty::validate', $resource, Openskos::CONCEPTBASEURI, false, $this->getErrorMessages());
    }
}
