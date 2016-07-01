<?php

namespace OpenSkos2\Validator;

use OpenSkos2\SkosCollection as SkosCollection;
use OpenSkos2\Rdf\Resource as RdfResource;

abstract class AbstractSkosCollectionValidator extends AbstractResourceValidator
{
    /**
     * @param RdfResource $resource
     * @return bool
     */
    public function validate(RdfResource $resource)
    {
        if ($resource instanceof SkosCollection) {
            $this->errorMessages = array_merge($this->errorMessages, $this->validateSkosCollection($resource));
            return (count($this->errorMessages) ===0);
        }
        return false;
    }
    
    abstract protected function validateSkosCollection(SkosCollection $skosCollection);
}