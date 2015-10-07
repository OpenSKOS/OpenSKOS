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
use OpenSkos2\File;
use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Rdf\Literal;
use OpenSkos2\Rdf\ResourceManager;
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
     * @var Tenant
     */
    protected $tenant;

    /**
     * Command constructor.
     * @param ResourceManager $resourceManager
     * @param Tenant $tenant optional If specified - tenant specific validation can be made.
     */
    public function __construct(ResourceManager $resourceManager, Tenant $tenant = null)
    {
        $this->resourceManager = $resourceManager;
        $this->tenant = $tenant;
    }


    public function handle(Message $message)
    {
        $file = new File($message->getFile());
        $resourceCollection = $file->getResources();

        // @TODO Most of the code below has to be applied for the api and for the api,
        // so has to be moved to a shared place.
        
        
        // Srtuff needed before validation.
        foreach ($resourceCollection as $resourceToInsert) {
            // Concept only logic
            // Generate uri if none or blank (_:genid<n>) is given.
            if ($resourceToInsert instanceof Concept) {
                $resourceToInsert->addProperty(\OpenSkos2\Namespaces\OpenSkos::COLLECTION, $message->getCollection());
                
                if ($resourceToInsert->isBlankNode()) {
                    $resourceToInsert->selfGenerateUri();
                }
            }
        }
        
        $validator = new \OpenSkos2\Validator\Collection($this->resourceManager, $this->tenant);
        if (!$validator->validate($resourceCollection, $this->logger)) {
            throw new \Exception('Failed validation');
        }

        if ($message->getClearCollection()) {
            $this->resourceManager->deleteBy([\OpenSkos2\Namespaces\OpenSkos::COLLECTION => $message->getCollection()]);
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
                $nonLangProperties = [Skos::NOTATION, OpenSkos::STATUS];
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

                $resourceToInsert->setProperty(
                    DcTerms::MODIFIED,
                    new Literal(date('c'), null, Literal::TYPE_DATETIME)
                );

                $resourceToInsert->setProperty(
                    DcTerms::CONTRIBUTOR,
                    $message->getUser()
                );

                if (!$resourceToInsert->hasProperty(DcTerms::DATESUBMITTED)) {
                    $resourceToInsert->setProperty(
                        DcTerms::DATESUBMITTED,
                        new Literal(date('c'), null, Literal::TYPE_DATETIME)
                    );
                }

                if (!$resourceToInsert->hasProperty(DcTerms::CREATOR)) {
                    $resourceToInsert->setProperty(
                        DcTerms::CREATOR,
                        $message->getUser()
                    );
                }


                $oldStatus = $currentVersion? $currentVersion->getStatus(): null;
                if ($oldStatus !== $resourceToInsert->getStatus()) {
                    //status change
                    $resourceToInsert->unsetProperty(DcTerms::DATEACCEPTED);
                    $resourceToInsert->unsetProperty(OpenSkos::ACCEPTEDBY);
                    $resourceToInsert->unsetProperty(OpenSkos::DATE_DELETED);
                    $resourceToInsert->unsetProperty(OpenSkos::DELETEDBY);

                    switch ($resourceToInsert->getStatus()) {
                        case \OpenSkos2\Concept::STATUS_APPROVED:
                            $resourceToInsert->addProperty(
                                DcTerms::DATEACCEPTED,
                                new Literal(date('c'), null, Literal::TYPE_DATETIME)
                            );
                            $resourceToInsert->addProperty(OpenSkos::ACCEPTEDBY, $message->getUser());
                            break;
                        case \OpenSkos2\Concept::STATUS_DELETED:
                            $resourceToInsert->addProperty(
                                OpenSkos::DATE_DELETED,
                                new Literal(date('c'), null, Literal::TYPE_DATETIME)
                            );
                            $resourceToInsert->addProperty(OpenSkos::DELETEDBY, $message->getUser());
                    }
                }
            }
            
            if (isset($currentVersions[$resourceToInsert->getUri()])) {
                $this->resourceManager->delete($currentVersions[$resourceToInsert->getUri()]);
            }
            $this->resourceManager->insert($resourceToInsert);
        }
    }
}
