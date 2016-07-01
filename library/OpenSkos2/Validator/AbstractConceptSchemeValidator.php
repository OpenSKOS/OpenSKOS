<?php

namespace OpenSkos2\Validator;

use OpenSkos2\ConceptScheme;
use OpenSkos2\Rdf\Resource as RdfResource;

abstract class AbstractConceptSchemeValidator extends AbstractResourceValidator
{
    /**
     * @param RdfResource $resource
     * @return bool
     */
    public function validate(RdfResource $resource)
    {
        if ($resource instanceof ConceptScheme) {
            $this->errorMessages = array_merge($this->errorMessages, $this->validateSchema($resource));
            return (count($this->errorMessages) ===0);
        }
        return false;
    }
    
    abstract protected function validateSchema(ConceptScheme $schema);
}