<?php

namespace OpenSkos2\Validator\ConceptScheme;

use OpenSkos2\ConceptScheme;
use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Validator\AbstractConceptSchemeValidator;

class Creator extends AbstractConceptSchemeValidator
{

   
     //validateProperty(RdfResource $resource, $propertyUri, $isRequired,
    //$isSingle, $isUri, $isBoolean, $isUnique,  $type)
    protected function validateSchema(ConceptScheme $resource)
    {
        return $this->validateProperty($resource, DcTerms::CREATOR, false, true, false, false, \OpenSkos2\Person::TYPE);
    }
}
