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

class Command
{
    /**
     * @var ResourceManager
     */
    private $resourceManager;

    /**
     * Command constructor.
     * @param ResourceManager $resourceManager
     */
    public function __construct(ResourceManager $resourceManager)
    {
        $this->resourceManager = $resourceManager;
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
            $this->resourceManager,
            $format,
            $message->getSearchPatterns()
        );
        
        if ($message->getOutputFilePath()) {
            $serialiser->writeToFile($message->getOutputFilePath());
        } else {
            return $serialiser->writeToString();
        }
    }
}
