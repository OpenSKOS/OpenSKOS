<?php

namespace OpenSkos2\Validator\GenericProperties;

use OpenSkos2\Rdf\Resource as RdfResource;
use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Validator\GenericProperties\AbstractProperty;

class Title extends AbstractProperty
{
     // parent::validate(RdfResource $resource, $propertyUri, $isRequired, $isSingle, $isUri, $isBoolean, $isUnique,  $type, $isForUpdate)
    
    public static function validate(RdfResource $resource, $isForUpdate)
    {
        $retVal = parent::validate($resource, DcTerms::TITLE, true,false, false, false, true, $isForUpdate);
        
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
