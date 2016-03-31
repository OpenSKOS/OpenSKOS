<?php

namespace OpenSkos2\Validator\CommonProperties;

use OpenSkos2\Rdf\Resource as RdfResource;
use OpenSkos2\Namespaces\DcTerms;
class Title implements CommonPropertyInterface
{
    public static function validate(RdfResource $resource)
    {
        $retVal = NonUniqueObligatoryProperty::validate($resource, DcTerms::TITLE, false);
        $languages = $resource->retrieveLanguages();
        foreach ($languages as $language) {
            $title = $resource->retrievePropertyInLanguage(DcTerms::TITLE, $language);
            if (!count(array_filter($title))) {
                $retVal[] = 'Title is required in all languages.';
            }
        }
        return $retVal;
    }
}
