<?php

namespace OpenSkos2\Api;

use OpenSkos2\Api\AbstractTripleStoreResource;
use OpenSkos2\TenantManager;
class Tenant extends AbstractTripleStoreResource
{
    public function __construct(TenantManager $manager) {
        $this->manager = $manager;
    }
    
}