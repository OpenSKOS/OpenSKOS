<?php

/**
 * OpenSKOS
 *
 * LICENSE
 *
 * This source file is subject to the GPLv3 license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @category   OpenSKOS
 * @package    OpenSKOS
 * @copyright  Copyright (c) 2015 Picturae (http://www.picturae.com)
 * @author     Picturae
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
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
        
        if ($this->resourceType !== null && $newval->getType() != $this->resourceType) {
            throw new InvalidResourceTypeException(
                'Can not insert resource of type <' . $newval->getType() . '>. '
                . 'The collection requires type <' . $this->resourceType . '>'
            );
        }
        
        parent::offsetSet($index, $newval);
    }
    
    /**
     * Finds resource by the specified uri.
     * @param string $uri
     * @return OpenSkos2\Rdf\Resource
     */
    public function findByUri($uri)
    {
        foreach ($this as $resource) {
            if ($resource->getUri() == $uri) {
                return $resource;
            }
        }
        return null;
    }
    
    /**
     * Appends other collection to this one.
     * @param \OpenSkos2\Rdf\ResourceCollection $otherCollection
     */
    public function merge(ResourceCollection $otherCollection)
    {
        foreach ($otherCollection as $resource) {
            $this->append($resource);
        }
    }
}
