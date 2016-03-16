<?php

namespace OpenSkos2\Validator\Set;

use OpenSkos2\Rdf\Resource as RdfResource;
use OpenSkos2\Validator\CommonProperties;

use OpenSkos2\Namespaces\Openskos;

class OpenskosOAIBaseUri extends AbstractSetValidator
{
    protected function validateSet(RdfResource $resource)
    {
        return CommonProperties\UniqueOptionalProperty::validate($resource, Openskos::OAI_BASEURL, false, $this->getErrorMessages());
    }
}
