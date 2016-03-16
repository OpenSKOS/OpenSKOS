<?php

namespace OpenSkos2\Validator\CommonProperties;

use OpenSkos2\Rdf\Resource as RdfResource;
use OpenSkos2\Namespaces\Openskos;
use OpenSkos2\Validator\CommonProperties\UniqueObligatoryProperty;

class Uuid implements CommonPropertyInterface
{
    public static function validate(RdfResource $resource)
    {
        $retVal = UniqueObligatoryProperty::validate($resource, Openskos::UUID, false);
        return $retVal;
    }
}
