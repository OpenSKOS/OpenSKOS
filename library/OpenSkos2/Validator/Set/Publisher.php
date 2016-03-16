<?php

namespace OpenSkos2\Validator\Set;

use OpenSkos2\Rdf\Resource as RdfResource;
use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Validator\CommonProperties;
class Publisher extends AbstractSetValidator
{
    
    protected function validateSet(RdfResource $resource)
    {
        $retVal = CommonProperties\UniqueObligatoryProperty::validate($resource, DcTerms::PUBLISHER, false, $this->getErrorMessages());
        return $retVal;
    }
}
