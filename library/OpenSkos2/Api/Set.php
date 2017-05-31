<?php

namespace OpenSkos2\Api;


use OpenSkos2\Search\Autocomplete;

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
    Autocomplete $searchAutocomplete,
    \OpenSkos2\PersonManager $personManager)
    {
        $this->manager = $manager;
        $this->authorisation = new \OpenSkos2\Authorisation($manager);
        $this->deletion = new \OpenSkos2\Deletion($manager);
        $this->personManager = $personManager;
        $this->init = parse_ini_file(__DIR__ . '/../../../application/configs/application.ini');
    }
}
