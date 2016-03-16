<?php

namespace OpenSkos2\Validator\SkosCollection;

use OpenSkos2\Rdf\Resource as RdfResource;

use OpenSkos2\Validator\CommonProperties;
class Creator extends AbstractSkosCollectionValidator
{
  
    protected function validateSkosCollection(RdfResource $resource)
    {
        return CommonProperties\Creator::validate($resource, $this->getErrorMessages());
    }
}
