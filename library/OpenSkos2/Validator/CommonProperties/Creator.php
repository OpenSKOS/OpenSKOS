<?php

namespace OpenSkos2\Validator\CommonProperties;

use OpenSkos2\Rdf\Resource as RdfResource;

use OpenSkos2\Namespaces\DcTerms;
class Creator implements CommonPropertyInterface
{
    
    public static function validate(RdfResource $resource)
    {
        $retVal = UniqueObligatoryProperty::validate($resource, DcTerms::CREATOR, false);
        return $retVal;
    }
}
