<?php

namespace OpenSkos2\Api;

use OpenSkos2\Api\AbstractTripleStoreResource;
use OpenSkos2\Api\Exception\ApiException;
use OpenSkos2\Namespaces\vCard;
use OpenSkos2\Namespaces\Org;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\TenantManager;
use OpenSkos2\MyInstitutionModules\Authorisation;
use OpenSkos2\MyInstitutionModules\Deletion;

class Tenant extends AbstractTripleStoreResource
{
    public function __construct(TenantManager $manager) {
        $this->manager = $manager;
        $this->authorisationManager = new Authorisation($manager);
        $this->deletionManager = new Deletion($manager);
    }
    
  
     // specific content validation
    protected function validate($resourceObject, $tenant) {
       parent::validate($resourceObject, $tenant);
       $name = $this -> getInstitutionName($resourceObject);
       $insts= $this -> manager -> fetchSubjectWithPropertyGiven(vCard::ORGNAME, '"'.$name.'"');
       if (count($insts)>0) {
           throw new ApiException('The institution with the name ' . $name . ' has been already registered.', 400);
       }
       $this->validatePropertyForCreate($resourceObject, OpenSkos::CODE, Org::FORMALORG);
    }
    
     // specific content validation
    protected function validateForUpdate($resourceObject, $tenant,  $existingResourceObject) {
        parent::validateForUpdate($resourceObject, $tenant, $existingResourceObject);
        
        // check the  name and the code (if they are new)
        $name = $this->getInstitutionName($resourceObject);
        $oldName = $this->getInstitutionName($existingResourceObject);
        if ($name !== $oldName) {
            // new name should not occur amnogst existing institution names
            $insts = $this->manager->fetchSubjectWithPropertyGiven(vCard::ORGNAME, '"'.$name.'"');
            if (count($insts) > 0) {
                throw new ApiException('The institution with the name ' . $name . ' has been already registered.', 400);
            }
        }
        $this->validatePropertyForUpdate($resourceObject, $existingResourceObject, OpenSkos::CODE, Org::FORMALORG);
    }
    
    private function getInstitutionName($inst) {
       $org = $inst->getProperty(vCard::ORG);
       $name= $org[0] -> getProperty(vCard::ORGNAME);
       return trim($name[0]); 
    }
    
   
}