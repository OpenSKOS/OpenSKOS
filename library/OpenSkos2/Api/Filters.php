<?php

namespace OpenSkos2\Api;


use OpenSkos2\Rdf\ResourceManager;

class Filters {

private $manager;

    public function __construct(ResourceManager $manager) {
        $this->manager = $manager;
    }
    
    public function fetchFilters(){
      $response = $this ->manager -> fetchResourceFilters();
      return $response;
    }
    
    public function fetchFiltersForRelations(){
      $response = $this ->manager -> fetchResourceFiltersForRelations();
      return $response;
    }
}