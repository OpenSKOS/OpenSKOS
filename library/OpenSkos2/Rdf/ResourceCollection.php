<?php
/**
 * Created by PhpStorm.
 * User: jsmit
 * Date: 26/08/2015
 * Time: 14:57
 */

namespace OpenSkos2\Rdf;

use OpenSkos2\Exception\InvalidResourceTypeException;

class ResourceCollection extends \ArrayObject
{
   //@todo add typehint and dockblocks
    
    /**
     * What is the basic resource for this collection.
     * Made to be extended and overwrited.
     * @var string NULL means any resource.
     */
    protected $resourceType = null;
    
    /**
     * @param mixed $index
     * @param Resource $newval
     * @throws InvalidResourceTypeException
     */
    public function offsetSet($index, $newval)
    {
        if (!$newval instanceof Resource) {
            throw new \RuntimeException(
                'You can add only Resource objects in ResourceCollection'
            );
        }
        
        if ($newval->getType() != $this->resourceType) {
            throw new InvalidResourceTypeException(
                'Can not insert resource of type <' . $newval->getType() . '>. '
                . 'The collection requires type <' . $this->resourceType . '>'
            );
        }
        
        parent::offsetSet($index, $newval);
    }
}
