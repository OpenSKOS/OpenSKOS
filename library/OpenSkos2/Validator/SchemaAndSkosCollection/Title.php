<?php

namespace OpenSkos2\Validator\SchemaAndSkosCollection;

use OpenSkos2\Rdf\Resource as RdfResource;
use OpenSkos2\Namespaces\DcTerms;
class Title extends AbstractSchemaAndSkosCollectionValidator
{
    /**
     * Ensure the preflabel does not already exists in the scheme
     * @param Concept $concept
     * @return bool
     */
    protected function validateSchemaOrSkosCollection(RdfResource $resource)
    {
        $languages = $resource->retrieveLanguages();
        foreach ($languages as $language) {
            $title = $resource->retrievePropertyInLanguage(DcTerms::TITLE, $language);
            if (!count(array_filter($title))) {
                $this->errorMessages[] = 'Title is required in all languages.';
                return false;
            }
        }
        return true;
    }
}
