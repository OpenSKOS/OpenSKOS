<?php

namespace OpenSkos2\Validator\Schema;

use OpenSkos2\Rdf\Resource as RdfResource;

use OpenSkos2\Validator\CommonProperties;
class Creator extends AbstractSchemaValidator
{
  
    protected function validateSchema(RdfResource $resource)
    {
        return CommonProperties\Creator::validate($resource, $this->getErrorMessages());
    }
}
