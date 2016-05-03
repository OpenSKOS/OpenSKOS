<?php

namespace OpenSkos2\Validator\CommonProperties;

use OpenSkos2\Rdf\Resource as RdfResource;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Validator\CommonProperties\NonUniqueOptionalProperty;

class InSkosCollection implements CommonPropertyInterface
{
    
    public static function validate(RdfResource $resource)
    {
        $retVal = NonUniqueOptionalProperty::validate($resource, OpenSkos::INSKOSCOLLECTION, false);
        return $retVal;
    }
}