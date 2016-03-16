<?php

namespace OpenSkos2\Validator\Set;

use OpenSkos2\Rdf\Resource as RdfResource;
use OpenSkos2\Validator\CommonProperties;
class Type extends AbstractSetValidator
{
    
    protected function validateSet(RdfResource $resource)
    {
        $retVal = CommonProperties\Type::validate($resource, $this->getErrorMessages());
        return $retVal;
    }
}
