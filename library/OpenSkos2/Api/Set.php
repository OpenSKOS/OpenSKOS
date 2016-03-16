<?php



namespace OpenSkos2\Api;

use OpenSkos2\SetManager;

class Set extends AbstractTripleStoreResource
{
    public function __construct(SetManager $manager) {
        $this->manager = $manager;
    }
}
