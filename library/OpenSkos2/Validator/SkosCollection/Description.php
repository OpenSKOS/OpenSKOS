<?php

namespace OpenSkos2\Validator\SkosCollection;

use OpenSkos2\Rdf\Resource as RdfResource;
use OpenSkos2\Validator\CommonProperties;

class Description extends AbstractSkosCollectionValidator
{
   protected function validateSkosCollection(RdfResource $resource)
    {
        return CommonProperties\Description::validate($resource, $this->getErrorMessages());
    }
}
