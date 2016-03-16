<?php

namespace OpenSkos2\Validator\Schema;

use OpenSkos2\Rdf\Resource as RdfResource;
use OpenSkos2\Validator\CommonProperties;

class Title extends AbstractSchemaValidator
{
    protected function validateSchema(RdfResource $resource)
    {
        return CommonProperties\Title::validate($resource, $this->getErrorMessages());
    }
}
