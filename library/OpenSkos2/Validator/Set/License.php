<?php

namespace OpenSkos2\Validator\Set;

use OpenSkos2\Rdf\Resource as RdfResource;
use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Validator\CommonProperties;
class Licesne extends AbstractSetValidator
{
    
    protected function validateSet(RdfResource $resource)
    {
        $retVal = CommonProperties\UniqueOptionalProperty::validate($resource, DcTerms::LICENSE, false, $this->getErrorMessages());
        return $retVal;
    }
}
