<?php

namespace OpenSkos2\Validator\CommonProperties;

use OpenSkos2\Rdf\Resource as RdfResource;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Validator\CommonProperties\NonUniqueObligatoryProperty;

class InSkosCollection implements CommonPropertyInterface
{
    
    public static function validate(RdfResource $resource)
    {
        $retVal = NonUniqueObligatoryProperty::validate($resource, OpenSkos::INSKOSCOLLECTION, false);
        return $retVal;
    }
}