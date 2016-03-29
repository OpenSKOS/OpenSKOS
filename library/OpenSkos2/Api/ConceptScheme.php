<?php

namespace OpenSkos2\Api;

use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\ConceptSchemeManager;

class ConceptScheme extends AbstractTripleStoreResource
{
    public function __construct(ConceptSchemeManager $manager) {
        $this->manager = $manager;
    }
    
     // specific content validation
     protected function validate($resourceObject, $tenantcode) {
       parent::validate($resourceObject, $tenantcode);
       
       //must be new
       $this->validatePropertyForCreate($resourceObject, DcTerms::TITLE, Skos::CONCEPTSCHEME);
    }
    
    
    // specific content validation
    protected function validateForUpdate($resourceObject, $tenantcode,  $existingResourceObject) {
        parent::validateForUpdate($resourceObject, $tenantcode, $existingResourceObject);
        
        // must not occur as another schema's name if different from the old one 
        $this->validatePropertyForUpdate($resourceObject, $existingResourceObject, DcTerms::TITLE, Skos::CONCEPTSCHEME);
    }
}