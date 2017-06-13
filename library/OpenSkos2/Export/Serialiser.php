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
use OpenSkos2\Rdf\ResourceCollection;
use OpenSkos2\Export\Serialiser\FormatAbstract;
use OpenSkos2\Exception\OpenSkosException;
use OpenSkos2\Search\Autocomplete;
use OpenSkos2\Tenant;
use OpenSkos2\Concept;
use OpenSkos2\ConceptManager;
class Serialiser
{
    /**
     * Holds the number of concepts that can be exported at a time.
     * @var int
     */
    const EXPORT_STEP = 1000;
    
    /**
     * The tenant in which context is the export.
     * @var Tenant
     */
    protected $tenant;
    
    /**
     * The concept manager to use for some concept related activities.
     * @var ConceptManager
     */
    protected $conceptManager;
    
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
     * @param Tenant $tenant
     * @param ConceptManager $conceptManager
     * @param FormatAbstract $format
     */
    public function __construct(Tenant $tenant, ConceptManager $conceptManager, FormatAbstract $format)
    {
        $this->tenant = $tenant;
        $this->conceptManager = $conceptManager;
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
            
            foreach ($resources as $resource) {
                if ($resource instanceof Concept && $this->tenant->getEnableSkosXl()) {
                    $resource->loadFullXlLabels($this->conceptManager->getLabelManager());
                }
                
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
            $collection = $this->conceptManager->fetchByUris($this->uris);
            $hasMore = false;
        }
        
        return $collection;
    }
}