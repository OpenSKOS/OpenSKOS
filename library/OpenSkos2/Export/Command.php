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

use OpenSkos2\Concept;
use OpenSkos2\ConceptManager;
use OpenSkos2\Export\Serialiser\FormatFactory;
use OpenSkos2\Search\Autocomplete;

class Command
{
    /**
     * @var ConceptManager
     */
    protected $conceptManager;
    
    /**
     * Searcher for when search options are provided.
     * @var Autocomplete
     */
    protected $searchAutocomplete;
    /**
     * @param Autocomplete $searchAutocomplete
     * @param ConceptManager $conceptManager
     */
    public function __construct(
        Autocomplete $searchAutocomplete,
        ConceptManager $conceptManager
    ) {
        $this->searchAutocomplete = $searchAutocomplete;
        $this->conceptManager = $conceptManager;
    }
    
    /**
     * Writes the export file.
     * @param Message $message
     */
    public function handle(Message $message)
    {
        if ($message->getTenant()->getEnableSkosXl()) {
            $excludeProperties = Concept::$classes['LexicalLabels'];
        } else {
            $excludeProperties = Concept::$classes['SkosXlLabels'];
        }
        
        $format = FormatFactory::create(
            $message->getFormat(),
            $message->getPropertiesToExport(),
            $this->conceptManager->fetchNamespaces(),
            $message->getMaxDepth(),
            $excludeProperties,
            $this->conceptManager
        );
        
        $serialiser = new Serialiser(
            $message->getTenant(),
            $this->conceptManager,
            $format
        );
        
        $searchOptions = $message->getSearchOptions();
        if (!empty($searchOptions)) {
            $serialiser->setSearchOptions($searchOptions);
            $serialiser->setSearchAutocomplete($this->searchAutocomplete);
        } else {
            $serialiser->setUris($message->getUris());
        }
        
        if ($message->getOutputFilePath()) {
            $serialiser->writeToFile($message->getOutputFilePath());
        } else {
            return $serialiser->writeToString();
        }
    }
}
