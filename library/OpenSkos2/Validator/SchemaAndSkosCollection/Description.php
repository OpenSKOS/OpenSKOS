<?php

namespace OpenSkos2\Validator\SchemaAndSkosCollection;

use OpenSkos2\Rdf\Resource as RdfResource;
class Description extends AbstractSchemaAndSkosCollectionValidator
{
    /**
     * Ensure the preflabel does not already exists in the scheme
     * @param Concept $concept
     * @return bool
     */
    protected function validateSchemaOrSkosCollection(RdfResource $resource)
    {
        return true;
    }
}
