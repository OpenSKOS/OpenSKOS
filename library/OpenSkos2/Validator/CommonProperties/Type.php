<?php

namespace OpenSkos2\Validator\CommonProperties;

use OpenSkos2\Rdf\Resource as RdfResource;
use OpenSkos2\Namespaces\Rdf;

use OpenSkos2\Validator\CommonProperties\UniqueObligatoryProperty;

class Type implements CommonPropertyInterface
{
    public static function validate(RdfResource $resource)
    {
        $retVal = UniqueObligatoryProperty::validate($resource, Rdf::TYPE, false);
        return $retVal;
    }
}
