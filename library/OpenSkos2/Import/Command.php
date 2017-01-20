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
use OpenSkos2\ConceptScheme;
use OpenSkos2\Exception\ResourceNotFoundException;
use OpenSkos2\Converter\File;
use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Rdf\Literal;
use OpenSkos2\Rdf\ResourceManager;
use OpenSkos2\ConceptManager;
use OpenSkos2\Tenant;
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
     * @var ConceptManager
     */
    private $conceptManager;

    /**
     * @var Tenant
     */
    protected $tenant;

    /**
     * Command constructor.
     * @param ResourceManager $resourceManager
     * @param Tenant $tenant optional If specified - tenant specific validation can be made.
     */
    public function __construct(
        ResourceManager $resourceManager,
        ConceptManager $conceptManager,
        Tenant $tenant = null
    ) {
        $this->resourceManager = $resourceManager;
        $this->conceptManager = $conceptManager;
        $this->tenant = $tenant;
    }


    public function handle(Message $message)
    {
        $file = new File($message->getFile());
        $resourceCollection = $file->getResources();

        // Disable commit's for every concept
        $this->resourceManager->setIsNoCommitMode(true);

        // Srtuff needed before validation.
        foreach ($resourceCollection as $resourceToInsert) {
            // Concept only logic
            // Generate uri if none or blank (_:genid<n>) is given.
            if ($resourceToInsert instanceof Concept) {
                $resourceToInsert->addProperty(\OpenSkos2\Namespaces\OpenSkos::SET, $message->getSetUri());

                if ($resourceToInsert->isBlankNode()) {
                    $resourceToInsert->selfGenerateUri($this->tenant, $this->conceptManager);
                }
            }
        }

        $validator = new \OpenSkos2\Validator\Collection($this->resourceManager, $this->tenant);
        if (!$validator->validate($resourceCollection, $this->logger)) {
            throw new \Exception('Failed validation: ' . PHP_EOL . implode(PHP_EOL, $validator->getErrorMessages()));
        }

        if ($message->getClearSet()) {
            $this->resourceManager->deleteBy([\OpenSkos2\Namespaces\OpenSkos::SET => $message->getSetUri()]);
        }

        if ($message->getDeleteSchemes()) {
            $conceptSchemes = [];
            foreach ($resourceCollection as $resourceToInsert) {
                foreach ($resourceToInsert->getProperty(Skos::INSCHEME) as $scheme) {
                    /** @var $scheme Uri */
                    $conceptSchemes[$scheme->getUri()] = $scheme;
                }
            }
            foreach ($conceptSchemes as $scheme) {
                $this->resourceManager->deleteBy([Skos::INSCHEME => $scheme]);
                $schemeUri = new \OpenSkos2\Rdf\Uri($scheme->getUri());
                $this->resourceManager->delete($scheme);
            }
        }

        $currentVersions = [];
        foreach ($resourceCollection as $resourceToInsert) {
            try {
                $uri = $resourceToInsert->getUri();
                $currentVersions[$resourceToInsert->getUri()] = $this->resourceManager->fetchByUri($uri);

                if ($message->getNoUpdates()) {
                    $this->logger->warning("Skipping concept {$uri}, because it already exists");
                    continue;
                }
            } catch (ResourceNotFoundException $e) {
                //do nothing
            }

            //special import logic
            if ($resourceToInsert instanceof Concept) {
                // @TODO Is that $currentVersion/DATESUBMITTED logic needed at all. Remove and test.
                $currentVersion = null;
                if (isset($currentVersions[$resourceToInsert->getUri()])) {
                    /**
                     * @var Resource $currentVersion
                     */
                    $currentVersion = $currentVersions[$resourceToInsert->getUri()];
                    if ($currentVersion->hasProperty(DcTerms::DATESUBMITTED)) {
                    }
                    if ($currentVersion->hasProperty(DcTerms::DATESUBMITTED)) {
                        $resourceToInsert->setProperty(
                            DcTerms::DATESUBMITTED,
                            $currentVersion->getProperty(DcTerms::DATESUBMITTED)[0]
                        );
                    }
                }


                if ($message->getIgnoreIncomingStatus()) {
                    $resourceToInsert->unsetProperty(OpenSkos::STATUS);
                }

                if ($message->getToBeChecked()) {
                    $resourceToInsert->addProperty(OpenSkos::TOBECHECKED, new Literal(true, null, Literal::TYPE_BOOL));
                }

                if ($message->getImportedConceptStatus() &&
                    (!$resourceToInsert->hasProperty(OpenSkos::STATUS))
                ) {
                    $resourceToInsert->addProperty(
                        OpenSkos::STATUS,
                        new Literal($message->getImportedConceptStatus())
                    );
                }

                // @TODO Those properties has to have types, rather then ignoring them from a list
                $nonLangProperties = [Skos::NOTATION, OpenSkos::TENANT, OpenSkos::STATUS];
                if ($message->getFallbackLanguage()) {
                    foreach ($resourceToInsert->getProperties() as $predicate => $properties) {
                        foreach ($properties as $property) {
                            if (!in_array($predicate, $nonLangProperties)
                                    && $property instanceof Literal
                                    && $property->getType() === null
                                    && $property->getLanguage() === null) {
                                $property->setLanguage($message->getFallbackLanguage());
                            }
                        }
                    }
                }

                $resourceToInsert->ensureMetadata(
                    $this->tenant->getCode(),
                    $message->getSetUri(),
                    $message->getUser(),
                    $currentVersion ? $currentVersion->getStatus(): null
                );
            } elseif ($resourceToInsert instanceof ConceptScheme) {
                $resourceToInsert->ensureMetadata(
                    $this->tenant->getCode(),
                    $message->getSetUri(),
                    $message->getUser()
                );
            }

            if (isset($currentVersions[$resourceToInsert->getUri()])) {
                $this->resourceManager->delete($currentVersions[$resourceToInsert->getUri()]);
            }
            $this->resourceManager->insert($resourceToInsert);
        }

        // Commit all solr documents
        $this->resourceManager->getSolrManager()->commit();
    }
}
