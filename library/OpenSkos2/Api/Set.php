<?php

namespace OpenSkos2\Api;

use OpenSkos2\Api\Exception\NotFoundException;

class Set extends AbstractTripleStoreResource
{

    /**
     * Search autocomplete
     *
     * @var Autocomplete
     */
    protected $searchAutocomplete;

    /**
     *
     * @param \OpenSkos2\SetManager $manager
     * @param \OpenSkos2\Search\Autocomplete $autocomplete
     * @param \OpenSkos2\PersonManager $personManager
     */
    public function __construct(
        \OpenSkos2\SetManager $manager,
        \OpenSkos2\Search\Autocomplete $searchAutocomplete,
        \OpenSkos2\PersonManager $personManager
    ) {
    
        $this->manager = $manager;
        $this->init = $this->manager->getInitArray();
         $this->deletion_integrity_check = new \OpenSkos2\IntegrityCheck($manager);
        $this->personManager = $personManager;
        
    }

    /**
     * Get openskos resource
     *
     * @param string|Uri $id
     * @throws NotFoundException
     * @return a sublcass of \OpenSkos2\Set
     */
    public function getResource($id)
    {
        try {
            $set = parent::getResource($id);
        } catch (\OpenSkos2\Exception\ResourceNotFoundException $ex) {
            $set = $this->manager->fetchByUuid($id, \OpenSkos2\Set::TYPE, 'openskos:code');
        }

        if (!$set) {
            throw new NotFoundException('Set not found by uri/uuid/code: ' . $id, 404);
        }
        return $set;
    }

  
    protected function getRequiredParameters()
    {
       
        return ['key', 'tenant'];
    }
    
     // no set is needed to implement set API
    
    protected function getSet($params, $tenant)
    {
        return new \OpenSkos2\Set();
    }
}
