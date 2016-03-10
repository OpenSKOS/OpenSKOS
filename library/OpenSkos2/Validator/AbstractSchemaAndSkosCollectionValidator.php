<?php

namespace OpenSkos2\Validator;

use OpenSkos2\Schema;
use OpenSkos2\SkosCollection;
use OpenSkos2\Rdf\Resource as RdfResource;

abstract class AbstractSchemaAndSkosCollectionValidator extends AbstractResourceValidator
{
    /**
     * @param RdfResource $resource
     * @return bool
     */
    public function validate(RdfResource $resource)
    {
        if ($resource instanceof Schema || $resource instanceof SkosCollection) {
            return $this->validateSchemaOrSkosCollection($resource);
        }
        return false;
    }
    
    abstract protected function validateSchemaOrSkosCollection(RdfResource $resource);
}