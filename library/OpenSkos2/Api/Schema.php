<?php

namespace OpenSkos2\Api;

use OpenSkos2\SchemaManager;

class Schema extends AbstractTripleStoreResource
{
    public function __construct(SchemaManager $manager) {
        $this->manager = $manager;
    }
}