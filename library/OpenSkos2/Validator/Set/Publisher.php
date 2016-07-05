<?php

namespace OpenSkos2\Validator\Set;


use OpenSkos2\Set;
use OpenSkos2\Validator\AbstractSetValidator;
use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Namespaces\Org;

class Publisher extends AbstractSetValidator
{
    
     //validateProperty(RdfResource $resource, $propertyUri, $isRequired, $isSingle, $isUri, $isBoolean, $isUnique,  $type)
    protected function validateSet(Set $resource){
        return $this->validateProperty($resource, DcTerms::PUBLISHER, true, true, false, false, Org::FORMALORG);
    }
}
