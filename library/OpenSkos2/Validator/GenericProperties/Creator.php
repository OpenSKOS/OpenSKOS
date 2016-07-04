<?php

namespace OpenSkos2\Validator\GenericProperties;

use OpenSkos2\Rdf\Resource as RdfResource;
use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Namespaces\Foaf;
use OpenSkos2\Validator\GenericProperties\AbstractProperty;

class Creator extends AbstractProperty
{
    
    // parent::validate(RdfResource $resource, $propertyUri, $isRequired, $isSingle, $isUri, $isBoolean, $isUnique, $type, $isForUpdate)
    public static function validate(RdfResource $resource)
    {
        $retVal = parent::validate($resource, DcTerms::CREATOR, true, true, true, false, false, Foaf::PERSON);
        return $retVal;
    }
}
