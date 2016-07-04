<?php

namespace OpenSkos2\Validator\GenericProperties;

use OpenSkos2\Rdf\Resource as RdfResource;
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Validator\GenericProperties\AbstractProperty;

class InScheme extends AbstractProperty
{
     // parent::validate(RdfResource $resource, $propertyUri, $isRequired, $isSingle, $isUri, $isBoolean, $isUnique,  $type, $isForUpdate)
    
    public static function validate(RdfResource $resource)
    {
        $retVal = parent::validate($resource, Skos::INSCHEME, true, false, true, false, false, Skos::CONCEPTSCHEME);
        return $retVal;
    }
}