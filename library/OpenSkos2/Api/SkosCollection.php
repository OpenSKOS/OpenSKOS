<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace OpenSkos2\Api;

class SkosCollection extends AbstractTripleStoreResource
{
    public function __construct(
        \OpenSkos2\SkosCollectionManager $manager,
        \OpenSkos2\PersonManager $personManager
    ) {
    
        $this->manager = $manager;
        $this->authorisation = new \OpenSkos2\Authorisation($manager);
        $this->deletion = new \OpenSkos2\Deletion($manager);
        $this->personManager = $personManager;
        $this->init = $this->manager->getInitArray();
        
    }
}
