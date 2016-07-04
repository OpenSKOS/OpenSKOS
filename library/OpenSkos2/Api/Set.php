<?php



namespace OpenSkos2\Api;


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
    
}
