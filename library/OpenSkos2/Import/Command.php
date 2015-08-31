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

namespace OpenSkos2\Import;


use OpenSkos2\Concept;
use OpenSkos2\Exception\ResourceNotFoundException;
use OpenSkos2\Rdf\Literal;
use OpenSkos2\Rdf\ResourceManager;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class Command implements LoggerAwareInterface
{
    use LoggerAwareTrait;

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


    public function handle(Message $message)
    {
        $file = new \OpenSkos2\File($message->getFile());
        $resourceCollection = $file->getResources();

        $validator = new \OpenSkos2\Validator\Validator();
        $validator->validateCollection($resourceCollection, $this->logger);

        if ($message->getClearCollection()) {


        }


        $currentVersions = [];
        foreach ($resourceCollection as $resourceToInsert) {

        }

        foreach ($resourceCollection as $resourceToInsert) {
            try {
                $uri = $resourceToInsert->getUri();
                $currentVersion[$resourceToInsert->getUri()] = $this->resourceManager->fetchByUri($uri);
                if ($message->getNoUpdates()) {
                    $this->logger->warning("Skipping concept {$uri}, because it already exists");
                    continue;
                }
            } catch (ResourceNotFoundException $e) {
                //do nothing
            }

            //special import logic
            if ($resourceToInsert instanceof Concept) {
                $resourceToInsert->addProperty(Concept::PROPERTY_COLLECTION, $message->getCollection());

                if ($message->getIgnoreIncomingStatus()) {
                    $resourceToInsert->unsetProperty(Concept::PROPERTY_OPENSKOS_STATUS);
                }

                if ($message->getToBeChecked()) {
                    $resourceToInsert->addProperty(Concept::PROPERTY_OPENSKOS_TOBECHECKED, $message->getCollection());
                }

                if ($message->getImportedConceptStatus() &&
                    (!$resourceToInsert->hasProperty(Concept::PROPERTY_OPENSKOS_STATUS))
                ) {
                    $resourceToInsert->addProperty(Concept::PROPERTY_OPENSKOS_STATUS,
                        new Literal($message->getImportedConceptStatus()));
                }

                if ($message->getFallbackLanguage()) {
                    foreach ($resourceToInsert->getProperties() as $properties) {
                        foreach ($properties as $property) {
                            if ($property instanceof Literal && $property->getLanguage() === null) {
                                $property->setLanguage($message->getFallbackLanguage());
                            }
                        }
                    }
                }
            }

            $this->resourceManager->insert($resourceToInsert);
        }
    }
}