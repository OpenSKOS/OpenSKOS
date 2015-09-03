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

namespace OpenSkos2\Export;

use OpenSkos2\Rdf\ResourceManager;
use OpenSkos2\Rdf\ResourceCollection;
use OpenSkos2\Export\Serialiser\FormatAbstract;

class Serialiser
{
    /**
     * Holds the number of concepts that can be exported at a time.
     * @var int
     */
    const EXPORT_STEP = 1000;
    
    /**
     * The resource manager to use for fetching the resources to serialise.
     * @var ResourceManager 
     */
    protected $resourceManager;
    
    // @TODO Maybe collection with query or something.
    /**
     * The query to use to fetch resources from the resource manager.
     * @var string 
     */
    protected $query;
    
    /**
     * @var FormatAbstract
     */
    protected $format;
    
    /**
     * @param ResourceManager $resourceManager
     * @param FormatAbstract $format
     * @param string $query
     */
    public function __construct(ResourceManager $resourceManager, FormatAbstract $format = null, $query = '')
    {
        $this->resourceManager = $resourceManager;
        $this->format = $format;
        $this->query = $query;
    }
    
    /**
     * Writes to string with the specified settings.
     * @return string
     */
    public function writeToString()
    {
        $streamHandle = fopen('php://memory', 'rw');
        $this->writeToStream($streamHandle);
        rewind($streamHandle);
        $result = stream_get_contents($streamHandle);
        fclose($streamHandle);
        return $result;
    }
    
    /**
     * Writes the serialised objects to file.
     * 
     * @param string $filePath
     */
    public function writeToFile($filePath)
    {
        $streamHandle = fopen($filePath, 'w');
        $this->writeToStream($streamHandle);
        fclose($streamHandle);
    }
    
    /**
     * Exports to the specified stream.
     * @param long $streamHandle
     */
    public function writeToStream($streamHandle)
    {
        fwrite($streamHandle, $this->format->printHeader());
        
        $step = self::EXPORT_STEP;
        $start = 0;
        $hasMore = false;
        do {
            $resources = $this->fetchResources($start, $step, $hasMore);
            
            foreach ($resources as $resource) {
                fwrite($streamHandle, $this->format->printResource($resource));
            }
        
            $start += $step;
        } while ($hasMore);
        
        fwrite($streamHandle, $this->format->printFooter());
    }
    
    /**
     * Fetches chunk of resources to serialise.
     * @param int $start
     * @param int $step
     * @param boolean $hasMore
     * @return ResourceCollection
     */
    protected function fetchResources($start, $step, &$hasMore)
    {
        // @TODO Sort
        
        $collection = $this->resourceManager->fetchWithLimit($this->query, $start, $step);
        
        // It may make it look once more at the end. But this way we don't need to count first.
        $hasMore = !(count($collection) < $step);
        
        return $collection;
    }
}
