<?php

namespace OpenSkos2\Validator\CommonProperties;

use OpenSkos2\Rdf\Resource as RdfResource;

class Description implements CommonPropertyInterface
{
    
    public static function validate(RdfResource $resource)
    {
        return array();
    }
}
