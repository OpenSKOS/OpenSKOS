<?php

namespace OpenSkos2\Api;

use OpenSkos2\Api\AbstractTripleStoreResource;
use OpenSkos2\TenantManager;
use OpenSkos2\Authorisation;
use OpenSkos2\Deletion;

class Tenant extends AbstractTripleStoreResource
{

    public function __construct(TenantManager $manager)
    {
        $this->manager = $manager;
        $this->authorisation = new Authorisation($manager);
        $this->deletion = new Deletion($manager);
    }
}
