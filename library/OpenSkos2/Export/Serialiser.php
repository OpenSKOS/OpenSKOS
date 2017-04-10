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
use OpenSkos2\Exception\OpenSkosException;
use OpenSkos2\Search\Autocomplete;

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
    
    /**
     * List of uris to export. Leave empty if search options are used (concepts only)
     * @var array
     */
    protected $uris;
    
    /**
     * The options to use to fetch resources from the search autocomplete (concepts only).
     * @var array
     */
    protected $searchOptions;
    
    /**
     * @var FormatAbstract
     */
    protected $format;
    
    /**
     * Searcher for when search options are provided.
     * @var \OpenSkos2\Search\Autocomplete
     */
    protected $searchAutocomplete;
    
    /**
     * Gets the list of uris to export. Leave empty if search options are used (concepts only)
     * @return array
     */
    public function getUris()
    {
        return $this->uris;
    }

    /**
     * Gets the options to use to fetch resources from the search autocomplete (concepts only).
     * @return array
     */
    public function getSearchOptions()
    {
        return $this->searchOptions;
    }

    /**
     * Sets the list of uris to export. Leave empty if search options are used (concepts only)
     * @param array $uris
     */
    public function setUris($uris)
    {
        $this->uris = $uris;
    }

    /**
     * Sets the options to use to fetch resources from the search autocomplete (concepts only).
     * @param array $searchOptions
     */
    public function setSearchOptions($searchOptions)
    {
        $this->searchOptions = $searchOptions;
    }
    
    /**
     * Gets searcher for when search options are provided.
     * @return OpenSkos2\Search\Autocomplete
     */
    public function getSearchAutocomplete()
    {
        if (empty($this->searchAutocomplete)) {
            throw new OpenSkosException('Search\Autocomplete required during export.');
        }
        return $this->searchAutocomplete;
    }

    /**
     * Sets searcher for when search options are provided.
     * @param \OpenSkos2\Search\Autocomplete $searchAutocomplete
     */
    public function setSearchAutocomplete(Autocomplete $searchAutocomplete)
    {
        $this->searchAutocomplete = $searchAutocomplete;
    }
    
    /**
     * Gets the resource manager to use for fetching the resources to serialise.
     * @return ResourceManager
     */
    public function getResourceManager()
    {
        if (empty($this->resourceManager)) {
            throw new OpenSkosException('Resource manager required during export.');
        }
        return $this->resourceManager;
    }

    /**
     * Gets the resource manager to use for fetching the resources to serialise.
     * @param ResourceManager $resourceManager
     */
    public function setResourceManager(ResourceManager $resourceManager)
    {
        $this->resourceManager = $resourceManager;
    }

    /**
     * @param FormatAbstract $format
     * @param Object[]|string $searchPatterns
     */
    public function __construct(FormatAbstract $format = null)
    {
        $this->format = $format;
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
            
            $resources->sortByPredicate(\OpenSkos2\Namespaces\Skos::PREFLABEL);
            
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
        if (!empty($this->searchOptions)) {
            $options = $this->searchOptions;
            $options['start'] = $start;
            $options['rows'] = $step;
            $collection = $this->getSearchAutocomplete()->search($options, $numFound);
            
            $hasMore = ($start + $step) < $numFound;
        } elseif (!empty($this->uris)) {
            $collection = $this->getResourceManager()->fetchByUris($this->uris);
            $hasMore = false;
        }
        
        return $collection;
    }
}
