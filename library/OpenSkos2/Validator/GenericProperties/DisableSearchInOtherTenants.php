<?php

namespace OpenSkos2\Validator\GenericProperties;

use OpenSkos2\Rdf\Resource as RdfResource;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Validator\GenericProperties\AbstractProperty;

class DisableSearchInOtherTenants extends AbstractProperty
{
    
    // parent::validate(RdfResource $resource, $propertyUri, $isRequired, $isSingle, $isUri, $isBoolean, $isUnique,  $type, $isForUpdate)
    public static function validate(RdfResource $resource)
    {
        $retVal = parent::validate($resource, OpenSkos::DISABLESEARCHINOTERTENANTS, true, true, false, true, false);
        return $retVal;
    }
}
