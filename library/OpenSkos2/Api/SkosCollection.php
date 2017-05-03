<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace OpenSkos2\Api;

use OpenSkos2\SkosCollectionManager;
use OpenSkos2\Authorisation;
use OpenSkos2\Deletion;

class SkosCollection extends AbstractTripleStoreResource
{

    public function __construct(SkosCollectionManager $manager)
    {
        $this->manager = $manager;
        $this->authorisation = new Authorisation($manager);
        $this->deletion = new Deletion($manager);
    }
}
