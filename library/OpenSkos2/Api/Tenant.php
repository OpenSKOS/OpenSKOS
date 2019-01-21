<?php

namespace OpenSkos2\Api;

use OpenSkos2\Api\Exception\NotFoundException;

class Tenant extends AbstractTripleStoreResource
{

    /**
     *
     * @param \OpenSkos2\TenantManager $manager
     * @param \OpenSkos2\PersonManager $personManager
     */
    public function __construct(
        \OpenSkos2\TenantManager $manager,
        \OpenSkos2\PersonManager $personManager
    ) {
    
        $this->manager = $manager;
        $this->customInit = $this->manager->getCustomInitArray();
        $this->deletionIntegrityCheck = new \OpenSkos2\IntegrityCheck($manager);
        $this->personManager = $personManager;
        $this->limit = $this->customInit['limit'];
    }

    /**
     * Get openskos resource
     *
     * @param string|Uri $id
     * @throws NotFoundException
     * @return a sublcass of \OpenSkos2\Tenant
     */
    public function getResource($id)
    {
        try {
            $tenant = parent::getResource($id);
        } catch (\OpenSkos2\Exception\ResourceNotFoundException $ex) {
            $tenant = $this->manager->fetchByUuid($id, \OpenSkos2\Tenant::TYPE, 'openskos:code');
        }

        if (!$tenant) {
            throw new NotFoundException('Tenant not found by uri/uuid/code: ' . $id, 404);
        }
        return $tenant;
    }

    protected function getRequiredParameters()
    {
        return ['key'];
    }
}
