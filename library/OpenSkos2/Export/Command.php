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
use OpenSkos2\Export\Serialiser\FormatFactory;
use OpenSkos2\Search\Autocomplete;

class Command
{
    /**
     * @var ResourceManager
     */
    private $resourceManager;
    
    /**
     * Searcher for when search options are provided.
     * @var \OpenSkos2\Search\Autocomplete
     */
    protected $searchAutocomplete;

    /**
     * Command constructor.
     * @param ResourceManager $resourceManager
     */
    public function __construct(ResourceManager $resourceManager, Autocomplete $searchAutocomplete)
    {
        $this->resourceManager = $resourceManager;
        $this->searchAutocomplete = $searchAutocomplete;
    }
    
    /**
     * Writes the export file.
     * @param Message $message
     */
    public function handle(Message $message)
    {
        $format = FormatFactory::create(
            $message->getFormat(),
            $message->getPropertiesToExport(),
            $this->resourceManager->fetchNamespaces(),
            $message->getMaxDepth()
        );
        
        $serialiser = new Serialiser(
            $format
        );
        
        $searchOptions = $message->getSearchOptions();
        if (!empty($searchOptions)) {
            $serialiser->setSearchOptions($searchOptions);
            $serialiser->setSearchAutocomplete($this->searchAutocomplete);
        } else {
            $serialiser->setUris($message->getUris());
            $serialiser->setResourceManager($this->resourceManager);
        }
        
        if ($message->getOutputFilePath()) {
            $serialiser->writeToFile($message->getOutputFilePath());
        } else {
            return $serialiser->writeToString();
        }
    }
}
