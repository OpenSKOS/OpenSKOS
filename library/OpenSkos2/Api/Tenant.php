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
    \OpenSkos2\TenantManager $manager, \OpenSkos2\PersonManager $personManager)
    {
        $this->manager = $manager;
        $this->authorisation = new \OpenSkos2\Authorisation($manager);
        $this->deletion = new \OpenSkos2\Deletion($manager);
        $this->personManager = $personManager;
        $this->init = parse_ini_file(__DIR__ . '/../../../application/configs/application.ini');
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
        } catch (ResourceNotFoundException $ex) {
            $tenant = $this->manager->fetchByCode($id, Tenant::TYPE);
        }

        if (!$tenant) {
            throw new NotFoundException('Set not found by uri/uuid/code: ' . $id, 404);
        }
        return $tenant;
    }

    protected function getRequiredParameters()
    {
        return ['key'];
    }

    // no set and tenatnt needed to implement institution API

    protected function getTenantFromParams($params)
    {
        return new \OpenSkos2\Tenant('dummy-tenant');
    }

    protected function getSet($params, $tenant)
    {
        return new \OpenSkos2\Set();
    }

}
