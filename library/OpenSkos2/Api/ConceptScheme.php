<?php

namespace OpenSkos2\Api;

use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\ConceptSchemeManager;
use OpenSkos2\MyInstitutionModules\Authorisation;
use OpenSkos2\MyInstitutionModules\Deletion;

class ConceptScheme extends AbstractTripleStoreResource
{
    public function __construct(ConceptSchemeManager $manager) {
        $this->manager = $manager;
        $this->authorisationManager = new Authorisation($manager);
        $this->deletionManager = new Deletion($manager);
    }
    
     // specific content validation
     protected function validate($resourceObject, $tenant) {
       parent::validate($resourceObject, $tenant);
       
       //must be new
       $this->validatePropertyForCreate($resourceObject, DcTerms::TITLE, Skos::CONCEPTSCHEME);
       
        // referred resources must exist  
       $resourceObject = $this -> validateSet($resourceObject);
       $this->validateURI($resourceObject, Skos::HASTOPCONCEPT, Skos::CONCEPT);
    }
    
    
    // specific content validation
    protected function validateForUpdate($resourceObject, $tenant,  $existingResourceObject) {
        parent::validateForUpdate($resourceObject, $tenant, $existingResourceObject);
        
        // must not occur as another schema's name if different from the old one 
        $this->validatePropertyForUpdate($resourceObject, $existingResourceObject, DcTerms::TITLE, Skos::CONCEPTSCHEME);
        
         // referred resources must exist  
        $this -> validateSet($resourceObject);
        $this->validateURI($resourceObject, Skos::HASTOPCONCEPT, Skos::CONCEPT);
    }
}