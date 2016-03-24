<?php



namespace OpenSkos2\Api;

use OpenSkos2\SetManager;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Namespaces\Dcmi;

use OpenSkos2\Api\Exception\ApiException;

class Set extends AbstractTripleStoreResource
{
    public function __construct(SetManager $manager) {
        $this->manager = $manager;
    }
    
     protected function validate($resourceObject, $tenantcode) {
       parent::validate($resourceObject, $tenantcode);
       $this->validatePropertyForCreate($resourceObject, DcTerms::TITLE, Dcmi::DATASET);
       $this->validatePropertyForCreate($resourceObject, OpenSkos::CODE, Dcmi::DATASET);
    }
    
    
    
    protected function validateForUpdate($resourceObject, $tenantcode,  $existingResourceObject) {
        parent::validateForUpdate($resourceObject, $tenantcode, $existingResourceObject);
        // check the  titles and the code (if they are new)
        $this->validatePropertyForUpdate($resourceObject, $existingResourceObject, DcTerms::TITLE, Dcmi::DATASET);
        $this->validatePropertyForUpdate($resourceObject, $existingResourceObject, OpenSkos::CODE, Dcmi::DATASET);
    }
}
