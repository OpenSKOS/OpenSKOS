<?php

namespace OpenSkos2\Api;

use OpenSkos2\Api\AbstractTripleStoreResource;
use OpenSkos2\Api\Exception\ApiException;
use OpenSkos2\Namespaces\vCard;
use OpenSkos2\TenantManager;
class Tenant extends AbstractTripleStoreResource
{
    public function __construct(TenantManager $manager) {
        $this->manager = $manager;
    }
    
    protected function validate($resourceObject, $tenantcode) {
       parent::validate($resourceObject, $tenantcode);
       $org = $resourceObject->getProperty(vCard::ORG);
       $name= $org[0] -> getProperty(vCard::ORGNAME);
       $insts= $this -> manager -> fetchSubjectWithPropertyGiven(vCard::ORGNAME, trim($name[0]));
       if (count($insts)>0) {
           throw new ApiException('The institution with the name ' . $name[0] . ' has been already registered.', 400);
       }
    }
}