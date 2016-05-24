<?php



namespace OpenSkos2\Api;

use OpenSkos2\Namespaces\Dcmi;
use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Namespaces\Org;
use OpenSkos2\SetManager;
use OpenSkos2\MyInstitutionModules\Authorisation;
use OpenSkos2\MyInstitutionModules\Deletion;

class Set extends AbstractTripleStoreResource
{
    public function __construct(SetManager $manager) {
        $this->manager = $manager;
        $this->authorisationManager = new Authorisation($manager);
        $this->deletionManager = new Deletion($manager);
    }
    
     // specific content validation
     protected function validate($resourceObject, $tenant) {
       parent::validate($resourceObject, $tenant);
       $this->validatePropertyForCreate($resourceObject, DcTerms::TITLE, Dcmi::DATASET);
       $this->validatePropertyForCreate($resourceObject, OpenSkos::CODE, Dcmi::DATASET);
       $this->validateURI($resourceObject, DcTerms::PUBLISHER,Org::FORMALORG);
    }
    
    
     // specific content validation
    protected function validateForUpdate($resourceObject, $tenant,  $existingResourceObject) {
        parent::validateForUpdate($resourceObject, $tenant, $existingResourceObject);
        // check the  titles and the code (if they are new)
        $this->validatePropertyForUpdate($resourceObject, $existingResourceObject, DcTerms::TITLE, Dcmi::DATASET);
        $this->validatePropertyForUpdate($resourceObject, $existingResourceObject, OpenSkos::CODE, Dcmi::DATASET);
        
        $this->validateURI($resourceObject, DcTerms::PUBLISHER,Org::FORMALORG);
    }
    
    
}
