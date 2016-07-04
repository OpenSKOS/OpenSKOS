<?php

namespace OpenSkos2\Validator\GenericProperties;

use OpenSkos2\Rdf\Resource as RdfResource;
use OpenSkos2\Namespaces\Openskos;
use OpenSkos2\Validator\GenericProperties\AbstractProperty;

class Uuid extends AbstractProperty
{
     // parent::validate(RdfResource $resource, $propertyUri, $isRequired, $isSingle, $isUri, $isBoolean, $isUnique,  $type, $isForUpdate)
   
    public static function validate(RdfResource $resource, $isForUpdate)
    {
        $retVal = parent::validate($resource, Openskos::UUID, true, true, false, false, true, $isForUpdate);
        return $retVal;
    }
}
