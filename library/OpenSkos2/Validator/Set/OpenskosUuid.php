<?php

namespace OpenSkos2\Validator\Set;

use OpenSkos2\Rdf\Resource as RdfResource;
use OpenSkos2\Validator\CommonProperties;

class OpenskosUuid extends AbstractSetValidator
{
    protected function validateSet(RdfResource $resource)
    {
        return CommonProperties\OpenskosUuid::validate($resource, $this->getErrorMessages());
    }
}
