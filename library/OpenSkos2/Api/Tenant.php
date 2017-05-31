<?php

namespace OpenSkos2\Api;

class Tenant extends AbstractTripleStoreResource
{

    /**
     *
     * @param \OpenSkos2\TenantManager $manager
     * @param \OpenSkos2\PersonManager $personManager
     */
    public function __construct(
    \OpenSkos2\TenantManager $manager, \OpenSkos2\PersonManager $personManager)
    {
        $this->manager = $manager;
        $this->authorisation = new \OpenSkos2\Authorisation($manager);
        $this->deletion = new \OpenSkos2\Deletion($manager);
        $this->personManager = $personManager;
        $this->init = parse_ini_file(__DIR__ . '/../../../application/configs/application.ini');
    }

}
