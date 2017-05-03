<?php

namespace OpenSkos2\Api;

use OpenSkos2\SetManager;
use OpenSkos2\Authorisation;
use OpenSkos2\Deletion;

class Set extends AbstractTripleStoreResource
{

    public function __construct(SetManager $manager)
    {
        $this->manager = $manager;
        $this->authorisation = new Authorisation($manager);
        $this->deletion = new Deletion($manager);
    }
}
