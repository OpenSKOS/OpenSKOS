<?php

namespace OpenSkos2\Api;

use OpenSkos2\Api\AbstractTripleStoreResource;
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
}
