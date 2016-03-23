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
       $code = $resourceObject->getProperty(OpenSkos::CODE);
       $sets= $this -> manager -> fetchSubjectWithPropertyGiven(OpenSkos::CODE, trim($code[0]),  Dcmi::DATASET);
       if (count($sets)>0) {
           throw new ApiException('The set with the code ' . $code[0]. ' has been already registered.', 400);
       }
       $title = $resourceObject->getProperty(DcTerms::TITLE);
       $sets2= $this -> manager -> fetchSubjectWithPropertyGiven(DcTerms::TITLE, trim($title[0]), Dcmi::DATASET);
       if (count($sets2)>0) {
           throw new ApiException('The set with the title ' . $title[0]. ' has been already registered.', 400);
       }
    }
}
