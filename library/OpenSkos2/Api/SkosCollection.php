<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace OpenSkos2\Api;

use OpenSkos2\SkosCollectionManager;

class SkosCollection extends AbstractTripleStoreResource
{
    public function __construct(SkosCollectionManager $manager) {
        $this->manager = $manager;
    }
}