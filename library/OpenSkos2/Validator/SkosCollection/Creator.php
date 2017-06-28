<?php

namespace OpenSkos2\Validator\SkosCollection;

use OpenSkos2\SkosCollection;
use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Validator\AbstractSkosCollectionValidator;

class Creator extends AbstractSkosCollectionValidator
{

     //validateProperty(RdfResource $resource, $propertyUri, $isRequired,
    //$isSingle, $isUri, $isBoolean, $isUnique,  $type)
    protected function validateSkosCollection(SkosCollection $resource)
    {
        return $this->validateProperty($resource, DcTerms::CREATOR, false, true, false, false, \OpenSkos2\Person::TYPE);
    }
}
