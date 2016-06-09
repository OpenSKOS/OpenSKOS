<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace OpenSkos2\Api;

use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Namespaces\Dcmi;
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\SkosCollectionManager;
use OpenSkos2\MyInstitutionModules\Authorisation;
use OpenSkos2\MyInstitutionModules\Deletion;

class SkosCollection extends AbstractTripleStoreResource
{
     public function __construct(SkosCollectionManager $manager) {
        $this->manager = $manager;
        $this->authorisationManager = new Authorisation($manager);
        $this->deletionManager = new Deletion($manager);
    }
    
     // specific content validation
     protected function validate($resourceObject, $tenant) {
       parent::validate($resourceObject, $tenant);
       
       //must be new
       $this->validatePropertyForCreate($resourceObject, DcTerms::TITLE, Skos::SKOSCOLLECTION);
       
       // referred resources must exist 
       $this->validateSet($resourceObject);
       $this->validateURI($resourceObject, Skos::MEMBER, Skos::CONCEPT);
    }
    
    
    // specific content validation
    protected function validateForUpdate($resourceObject, $tenant,  $existingResourceObject) {
        parent::validateForUpdate($resourceObject, $tenant, $existingResourceObject);
        
        // must not occur as another collection's name if different from the old one 
        $this->validatePropertyForUpdate($resourceObject, $existingResourceObject, DcTerms::TITLE, Skos::SKOSCOLLECTION);
    
         // referred resources must exist 
       $this->validateSet($resourceObject);
       $this->validateURI($resourceObject, Skos::MEMBER, Skos::CONCEPT);
    }
}