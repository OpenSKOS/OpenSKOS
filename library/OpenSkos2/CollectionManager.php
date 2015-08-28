<?php
namespace OpenSkos2;

use OpenSkos2\Rdf\ResourceManager;

/**
 * Created by PhpStorm.
 * User: jsmit
 * Date: 26/08/2015
 * Time: 15:55
 */
class CollectionManager extends ResourceManager
{
    /**
     * What is the basic resource for this manager.
     * @var string NULL means any resource.
     */
    protected $resourceType = Collection::TYPE;
}
