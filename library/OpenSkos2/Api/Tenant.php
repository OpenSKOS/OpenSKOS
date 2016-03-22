<?php

namespace OpenSkos2\Api;

use OpenSkos2\Api\AbstractTripleStoreResource;
use OpenSkos2\Api\Exception\ApiException;
use OpenSkos2\Namespaces\vCard;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\TenantManager;
class Tenant extends AbstractTripleStoreResource
{
    public function __construct(TenantManager $manager) {
        $this->manager = $manager;
    }
    
    protected function validate($resourceObject, $tenantcode) {
       parent::validate($resourceObject, $tenantcode);
       $name = $this -> getInstitutionName($resourceObject);
       $insts= $this -> manager -> fetchSubjectWithPropertyGiven(vCard::ORGNAME, $name);
       if (count($insts)>0) {
           throw new ApiException('The institution with the name ' . $name . ' has been already registered.', 400);
       }
       $code = $resourceObject->getProperty(OpenSkos::CODE);
       $insts2= $this -> manager -> fetchSubjectWithPropertyGiven(OpenSkos::CODE, trim($code[0]));
       if (count($insts2)>0) {
           throw new ApiException('The institution with the code ' . $code[0]. ' has been already registered.', 400);
       }
    }
    
    protected function validateForUpdate($resourceObject, $tenantcode,  $existingResourceObject) {
        parent::validate($resourceObject, $tenantcode);
        
        // do not update uuid: it must be intact forever, connected to uuid
        $uuid = $resourceObject->getProperty(OpenSkos::UUID);
        $oldUuid = $existingResourceObject ->getProperty(OpenSkos::UUID);
        if ($uuid[0]->getValue() !== $oldUuid[0]->getValue()) {
            throw new ApiException('You cannot change UUID of the resouce. Keep it ' . $oldUuid[0], 400);
        }
        // check the  name and the code (if they are new)
        $name = $this->getInstitutionName($resourceObject);
        $oldName = $this->getInstitutionName($existingResourceObject);
        if ($name !== $oldName) {
            // new name should not occur amnogst existing institution names
            $insts = $this->manager->fetchSubjectWithPropertyGiven(vCard::ORGNAME, $name);
            if (count($insts) > 0) {
                throw new ApiException('The institution with the name ' . $name . ' has been already registered.', 400);
            }
        }
        $code = $resourceObject->getProperty(OpenSkos::CODE);
        $oldCode = $existingResourceObject ->getProperty(OpenSkos::CODE);
        if ($code[0]->getValue() !== $oldCode[0]->getValue()) {
            // new code should not occur amnogst existing institution codes
            $insts2 = $this->manager->fetchSubjectWithPropertyGiven(OpenSkos::CODE, $code[0]);
            if (count($insts2) > 0) {
                throw new ApiException('The institution with the code ' . $oldCode[0] . ' has been already registered.', 400);
            }
        }
    }
    
    private function getInstitutionName($inst) {
       $org = $inst->getProperty(vCard::ORG);
       $name= $org[0] -> getProperty(vCard::ORGNAME);
       return trim($name[0]); 
    }
    
   
}