<?php

namespace OpenSkos2\Validator\Set;

use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Set;
use OpenSkos2\Validator\AbstractSetValidator;

class OpenskosOAIBaseUri extends AbstractSetValidator
{

    protected function validateSet(Set $resource)
    {
        //validateProperty(RdfResource $resource, $propertyUri, $isRequired, $isSingle, $isBoolean, $isUnique,  $type)
        return $this->validateProperty($resource, OpenSkos::OAI_BASEURL, false, true, false, false);
    }
}
