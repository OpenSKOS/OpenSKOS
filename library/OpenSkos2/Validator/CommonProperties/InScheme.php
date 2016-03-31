<?php

namespace OpenSkos2\Validator\CommonProperties;

use OpenSkos2\Rdf\Resource as RdfResource;
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Validator\CommonProperties\NonUniqueObligatoryProperty;

class InScheme implements CommonPropertyInterface
{
    
    public static function validate(RdfResource $resource)
    {
        $retVal = NonUniqueObligatoryProperty::validate($resource, Skos::INSCHEME, false);
        return $retVal;
    }
}