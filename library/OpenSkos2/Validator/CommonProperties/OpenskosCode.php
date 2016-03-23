<?php

namespace OpenSkos2\Validator\CommonProperties;

use OpenSkos2\Rdf\Resource as RdfResource;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Validator\CommonProperties\UniqueObligatoryProperty;

class OpenskosCode implements CommonPropertyInterface
{
    public static function validate(RdfResource $resource)
    {
        $retVal = UniqueObligatoryProperty::validate($resource, OpenSkos::CODE, false);
        return $retVal;
    }
}
