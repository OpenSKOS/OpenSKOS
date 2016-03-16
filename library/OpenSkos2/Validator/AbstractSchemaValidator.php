<?php

namespace OpenSkos2\Validator;

use OpenSkos2\Schema as Schema;
use OpenSkos2\Rdf\Resource as RdfResource;

abstract class AbstractSchemaValidator extends AbstractResourceValidator
{
    /**
     * @param RdfResource $resource
     * @return bool
     */
    public function validate(RdfResource $resource)
    {
        if ($resource instanceof Schema) {
            return $this->validateSchema($resource);
        }
        return false;
    }
    
    abstract protected function validateSchema(Schema $schema);
}