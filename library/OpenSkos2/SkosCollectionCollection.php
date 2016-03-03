<?php

namespace OpenSkos2;

use OpenSkos2\Rdf\ResourceCollection;

class SkosCollectionCollection extends ResourceCollection
{
    /**
     * What is the basic resource for this collection.
     * @var string NULL means any resource.
     */
    protected $resourceType = SkosCollection::TYPE;
}
