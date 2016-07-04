<?php

namespace OpenSkos2\Validator\GenericProperties;

use OpenSkos2\Rdf\Resource as RdfResource;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Validator\GenericProperties\AbstractProperty;

class WebPage extends AbstractProperty
{
    
    // parent::validate(RdfResource $resource, $propertyUri, $isRequired, $isSingle, $isUri, $isBoolean, $isUnique,  $type, $isForUpdate)
    public static function validate(RdfResource $resource, $isForUpdate)
    {
        $retVal = parent::validate($resource, OpenSkos::WEBPAGE, true, true, true, false, true, $isForUpdate);
        return $retVal;
    }
}
