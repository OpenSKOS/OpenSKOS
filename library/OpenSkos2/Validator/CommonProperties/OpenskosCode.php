<?php

namespace OpenSkos2\Validator\CommonProperties;

use OpenSkos2\Rdf\Resource as RdfResource;
use OpenSkos2\Namespaces\Openskos;
use OpenSkos2\Validator\CommonProperties\UniqueObligatoryProperty;

class OpenskosCode implements CommonPropertyInterface
{
    public static function validate(RdfResource $resource)
    {
        $retVal = UniqueObligatoryProperty::validate($resource, Openskos::CODE, false);
        return $retVal;
    }
}
