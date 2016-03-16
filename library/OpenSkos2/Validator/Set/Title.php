<?php

namespace OpenSkos2\Validator\Set;

use OpenSkos2\Rdf\Resource as RdfResource;
use OpenSkos2\Validator\CommonProperties;
use OpenSkos2\Namespaces\DcTerms;
class Title extends AbstractSetValidator
{
    
    protected function validateSet(RdfResource $resource)
    {
        $retVal = CommonProperties\UniqueObligatoryProperty::validate($resource, DcTerms::TITLE, false, $this->getErrorMessages());
        return $retVal;
    }
}