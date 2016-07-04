<?php

namespace OpenSkos2\Validator\GenericProperties;

use OpenSkos2\Rdf\Resource as RdfResource;
use OpenSkos2\Namespaces\Rdf;
use OpenSkos2\Validator\GenericProperties\AbstractProperty;

class Type extends AbstractProperty
{
     // parent::validate(RdfResource $resource, $propertyUri, $isRequired, $isSingle, $isUri, $isBoolean, $isUnique,  $type, $isForUpdate)
    
    public static function validate(RdfResource $resource)
    {
        $retVal = parent::validate($resource, Rdf::TYPE, true,true, true, false, false);
        return $retVal;
    }
}
