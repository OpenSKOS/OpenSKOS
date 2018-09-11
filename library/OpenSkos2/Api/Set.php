<?php

namespace OpenSkos2\Api;

use OpenSkos2\Api\Exception\NotFoundException;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Rdf\Literal;

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
     * @return a sublcass of \OpenSkos2\Collection
     */
    public function getResource($id)
    {
        try {
            $set = parent::getResource($id);
        } catch (\OpenSkos2\Exception\ResourceNotFoundException $ex) {
            //$set = $this->manager->fetchByUuid($id, \OpenSkos2\Collection::TYPE, 'openskos:code');

            $collection = $this->manager->fetch(
                [
                    OpenSkos::CODE => new Literal($id)
                ]
            );
            $collection = $collection[0];
        }

        if (!$collection) {
            throw new NotFoundException('Collection not found by uri/uuid/code: ' . $id, 404);
        }
        return $collection;
    }

  
    protected function getRequiredParameters()
    {
       
        return ['key', 'tenant'];
    }
    
     // no set is needed to implement set API
    
    protected function getSet($request)
    {
        return new \OpenSkos2\Collection();
    }
}
