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

use Exception;
use OpenSkos2\Concept;
use OpenSkos2\ConceptScheme;
use OpenSkos2\SkosCollection;
use OpenSkos2\Set;
use OpenSkos2\Logging;
use OpenSkos2\Converter\File;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Namespaces\Rdf;
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Rdf\Literal;
use OpenSkos2\Rdf\Resource;
use OpenSkos2\Rdf\ResourceManager;
use OpenSkos2\Rdf\Uri;
use OpenSkos2\Set;
use OpenSkos2\PersonManager;
use OpenSkos2\Tenant;
use OpenSkos2\Validator\Resource as ResourceValidator;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use SebastianBergmann\RecursionContext\Exception as Exception2;

// Meertens: 
// -- no need for ConceptManager since import runs also for other resources as well
// and Resource Manager will do the job.
// -- Tenant is not a constructor parameter because its Uri is derived from setUri passed via message in "handle".
// this derivation is not happening in the loop, but only one time, so it should not slow down the import process.
// -- the following Picturae's change of 21/10/2016 is not taken, because it is not clear where to locate it and why
// (after Meertens refactoring and adjusting for importing schemata): 
// "// Disable commit's for every concept"
// "$this->resourceManager->setIsNoCommitMode(true);"
// --  may be it does make to have two Command scripts, meertens version and picturae's version



class Command implements LoggerAwareInterface
{

    use LoggerAwareTrait;

    /**
     * @var ResourceManager
     */
    private $resourceManager;
    private $black_list;
    private $init;
    private $errorLog;

    /**
     * @var PersonManager
     */
    private $personManager;

    /**
     * @var Tenant
     */
    protected $tenant;

    /**
     * Command constructor.
     * @param ResourceManager $resourceManager
     * @param PersonManager $personManager
     */
    public function __construct(
    ResourceManager $resourceManager, Person $person, PersonManager $personManager, Tenant $tenant
    )
    {
        $this->resourceManager = $resourceManager;
        $this->personManager = $personManager;
        $this->person = $person;
        $this->tenant = $tenant;
        $this->init = $resourceManager->getInitArray();
        $this->erroLog = '../../../' . $this->init["custom.error_log"];
    }

    public function handle(Message $message)
    {
        $this->black_list = [];

        if ($message->isRemovingDanglingConceptReferencesRound()) {
            return $this->handleRemovingDanglingConceptReferencesRound($message);
        } else {
            return $this->handleWithoutRemovingDanglingReferences($message);
        }
    }

    private function handleWithoutRemovingDanglingReferences(Message $message)
    {
        // file to import
        $file = new File($message->getFile());
        $resourceCollection = $file->getResources();

        // set
        $setUri = $message->getSetUri();
        $set = $this->resourceManager->fetchByUri($setUri, Set::TYPE);


        //** Some purging stuff from the original picturae code
        if ($message->getClearSet()) {
            $this->resourceManager->deleteBy([OpenSkos::SET => $message->getSetUri()]);
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
                $this->resourceManager->delete($scheme, Skos::CONCEPTSCHEME);
            }
        }



        foreach ($resourceCollection as $resourceToInsert) {
            $uri = $resourceToInsert->getUri();

            // checking an rdf type
            $types = $resourceToInsert->getProperty(Rdf::TYPE);
            if (count($types) < 1) {
                throw new Exception("The resource " . $uri . " does not have rdf-type. ");
            }
            $type = $types[0]->getUri();

            // branching on if resource exists or does not
            $exists = $this->resourceManager->resourceExists($uri, $type);
            if ($exists) {
                $existingResource = $this->resourceManager->fetchByUri($uri, $type);
                if ($message->getNoUpdates()) {
                    var_dump("Skipping resource {$uri}, "
                        . "because it already exists and NoUpdates is set to true. ");
                    $this->logger->warning(
                        "Skipping resource {$uri}, because it already exists"
                        . " and NoUpdates is set to true."
                    );
                    continue;
                } else {
                    $isForUpdates = true;
                }
            } else {
                $existingResource = null;
                $isForUpdates = false;
            }

            if (type === Concept::TYPE) {
                $resourceToInsert = $this->specialConceptImportLogic(
                    $message, $setUri, $resourceToInsert, $existingResource
                );
            } else {
                $resourceToInsert->ensureMetadata($this->tenant->getUri(), $setUri, $this->person, $this->personManager, $existingResource);
            }

            $validator = new ResourceValidator(
                $this->resourceManager, $this->tenant, $set, $isForUpdates, true, false
            );
            $valid = $validator->validate($resourceToInsert);
            if (!$valid) {
                $this->handleInvalidResource($uri, $type, $validator, $resourceToInsert);
                continue;
            }
            $this->handleWarnings($uri, $validator);


            if ($existingResource !== null) {
                $this->resourceManager->delete($existingResource);
            }
            $this->resourceManager->insert($resourceToInsert);
            var_dump($resourceToInsert->getUri() . " has been inserted.");
        }
        return $this->black_list;
    }

    private function handleRemovingDanglingConceptReferencesRound(Message $message)
    {
        $conceptURIs = $this->resourceManager->fetchSubjectWithPropertyGiven(
            Rdf::TYPE, '<' . Concept::TYPE . '>'
        );

        $schemeURIs = $this->resourceManager->fetchSubjectWithPropertyGiven(
            Rdf::TYPE, '<' . ConceptScheme::TYPE . '>'
        );

        $skoscollectionURIs = $this->resourceManager->fetchSubjectWithPropertyGiven(
            Rdf::TYPE, '<' . SkosCollection::TYPE . '>'
        );

        $uris = array();
        $uris[Concept::TYPE] = $conceptURIs;
        $uris[ConceptScheme::TYPE] = $schemeURIs;
        $uris[SkosCollection::TYPE] = $skoscollectionURIs;

        // set
        $setUri = $message->getSetUri();
        $set = $this->resourceManager->fetchByUri($setUri, Set::TYPE);

        foreach ($uris as $type => $refs) {
            foreach ($refs as $uri) {
                $resource = $this->resourceManager->fetchByUri($uri, $type);
                $validator = new ResourceValidator(
                    $this->resourceManager, $this->tenant, $set, true, true, true
                );
                $valid = $validator->validate($resource);
                $cleanedResource = $this->removeDanglingReferences(
                    $resource, $validator->getDanglingReferences()
                );
                if ($type === Concept::TYPE) {
                    $cleanedResource = $this->specialConceptImportLogic(
                        $message, $setUri, $cleanedResource, $resource
                    );
                } else {
                    $cleanedResource->ensureMetadata($this->tenant->getUri(), $setUri, $this->person, $this->personManager, $resource);
                }
                $valid = $validator->validate($cleanedResource);
                if (!$valid) {
                    $this->handleInvalidResource($uri, $type, $validator, $cleanedResource);
                    continue;
                }
                $this->handleWarnings($uri, $validator);

                $this->resourceManager->insert($cleanedResource);
                var_dump($cleanedResource->getUri() . " has been updated.");
            }
        }
        return $this->black_list;
    }

    private function specialConceptImportLogic($message, $setUri, $conceptToInsert, $existingConcept)
    {
        if ($message->getIgnoreIncomingStatus()) {
            $conceptToInsert->unsetProperty(OpenSkos::STATUS);
        }

        if ($message->getToBeChecked()) {
            $conceptToInsert->addProperty(OpenSkos::TOBECHECKED, new Literal(true, null, Literal::TYPE_BOOL));
        }

        if ($message->getImportedConceptStatus() &&
            (!$conceptToInsert->hasProperty(OpenSkos::STATUS))
        ) {
            $conceptToInsert->addProperty(OpenSkos::STATUS, new Literal($message->getImportedConceptStatus()));
        }

        $langProperties = Resource::getLanguagedProperties();
        if ($message->getFallbackLanguage()) {
            foreach ($conceptToInsert->getProperties() as $predicate => $properties) {
                foreach ($properties as $property) {
                    if (in_array($predicate, $langProperties) &&
                        $property instanceof Literal && $property->getLanguage() === null) {
                        $property->setLanguage($message->getFallbackLanguage());
                    }
                }

                $conceptToInsert->ensureMetadata(
                    $this->tenant->getUri(), $setUri, $this->person, $this->personManager, $existingConcept
                );
            }
        }
        return $conceptToInsert;
    }

    private function handleInvalidResource($uri, $type, $validator, $resourceToInsert)
    {
        $this->black_list[] = $uri;
        foreach ($validator->getErrorMessages() as $errorMessage) {
            var_dump($errorMessage);
            Logging::varLogger(
                "The followig resource has not been added due "
                . "to the validation error " . $errorMessage, $resourceToInsert->getUri(), $this->errorLog
            );
        }
        var_dump($resourceToInsert->getUri() .
            " cannot not been inserted due to the validation error(s) above.");
        $this->resourceManager->delete($resourceToInsert, $type);
        $this->resourceManager->deleteReferencesToObject($resourceToInsert);
    }

    private function handleWarnings($uri, $validator)
    {
        foreach ($validator->getWarningMessages() as $warning) {
            var_dump($warning);
            Logging::varLogger($warning, $uri, $this->errorLog);
        }
    }

    private function removeDanglingReferences($resource, $danglings)
    {
        $properties = $resource->getProperties();
        foreach ($properties as $property => $values) {
            if (is_array($values)) {
                $checked_values = [];
                foreach ($values as $value) {
                    if ($values instanceof Uri) {
                        if (!in_array($value->getUri(), $danglings)) {
                            $checked_values[] = $value;
                        }
                    } else {
                        $checked_values[] = $value;
                    }
                }
                $resource->setProperties($property, $checked_values);
            } else {
                if ($values instanceof Uri) {
                    if (in_array($values->getUri(), $danglings)) {
                        $resource->unsetProperty($property);
                    }
                }
            }
        }
        return $resource;
    }

}
