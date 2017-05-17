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
use OpenSkos2\ConfigOptions;
use OpenSkos2\Converter\File;
use OpenSkos2\Namespaces\Dcmi;
use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Namespaces\Rdf;
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Preprocessor;
use OpenSkos2\Rdf\Literal;
use OpenSkos2\Rdf\Resource;
use OpenSkos2\Rdf\ResourceManager;
use OpenSkos2\Rdf\Uri;
use OpenSkos2\Set;
use OpenSkos2\Tenant;
use OpenSkos2\Validator\Resource as ResourceValidator;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use SebastianBergmann\RecursionContext\Exception as Exception2;
use Tools\Logging;

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

    /**
     * Command constructor.
     * @param ResourceManager $resourceManager
     * @param String $tenantUri optional If specified - tenant specific validation can be made.
     */
    public function __construct(ResourceManager $resourceManager)
    {
        $this->resourceManager = $resourceManager;
    }

    public function handle(Message $message)
    {
        $this->black_list = [];

        if ($message->isRemovingDanglingConceptReferencesRound($message)) {
            return $this->handleRemovingDanglingConceptReferencesRound($message);
        } else {
            return $this->handleWithoutRemovingDanglingReferences($message);
        }
    }

    private function handleWithoutRemovingDanglingReferences(Message $message)
    {

        $file = new File($message->getFile());
        $resourceCollection = $file->getResources();

        $set = $this->resourceManager->fetchByUri($message->getSetUri(), Set::TYPE);

        $tenantUris = $set->getProperty(DcTerms::PUBLISHER);
        if (count($tenantUris) < 1) {
            throw new Exception2(
                "The set " . $message->getSetUri() .
                " is supplied without a proper publisher (tenant, isntitution). "
            );
        }
        $tenantUri = $tenantUris[0]->getUri();
        $tenant = $this->resourceManager->fetchByUri($tenantUri, Tenant::TYPE);

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
            $types = $resourceToInsert->getProperty(Rdf::TYPE);

            if (count($types) < 1) {
                throw new Exception("The resource " . $uri . " does not have rdf-type. ");
            }
            $type = $types[0]->getUri();
            $preprocessor = new Preprocessor($this->resourceManager, $type, $message->getUser());

            $exists = $this->resourceManager->resourceExists($uri, $type);
            if ($exists) {
                $currentVersion = $this->resourceManager->fetchByUri($uri, $type);
                if ($message->getNoUpdates()) {
                    var_dump("Skipping resource {$uri}, "
                    . "because it already exists and NoUpdates is set to true. ");
                    $this->logger->warning(
                        "Skipping resource {$uri}, because it already exists"
                        . " and NoUpdates is set to true."
                    );
                    continue;
                } else {
                    $preprocessedResource = $preprocessor->forUpdate($resourceToInsert, $tenant, $set);
                    $isForUpdates = true;
                    if ($currentVersion->hasProperty(DcTerms::DATESUBMITTED)) {
                        $dateSubm = $currentVersion->getProperty(DcTerms::DATESUBMITTED);
                        $preprocessedResource->setProperty(DcTerms::DATESUBMITTED, $dateSubm[0]);
                    }
                }
            } else {
                // autoGenerateIdentifiers is set to false because:
                // uri's are supposed to be given in the input file
                // and uuid's will be added when necessary while preprocessing
                $preprocessedResource = $preprocessor->forCreation(
                    $resourceToInsert,
                    false,
                    $tenant,
                    $set
                );
                $isForUpdates = false;
                $currentVersion = null;
            }

            // __construct(ResourceManager $resourceManager, $isForUpdate, $tenant,
            // $set, $referenceCheckOn, $softConceptRelationValidation, LoggerInterface
            // $logger = null)
            $validator = new ResourceValidator(
                $this->resourceManager,
                $isForUpdates,
                $tenant,
                $set,
                true,
                true
            );
            $valid = $validator->validate($preprocessedResource);
            if (!$valid) {
                $this->handleNotValidResource($uri, $type, $validator, $preprocessedResource);
                continue;
            }
            $this->handleWarnings($uri, $validator);
            if ($preprocessedResource instanceof Concept) {
                $preprocessedResource = $this->specialConceptImportLogic(
                    $message,
                    $preprocessedResource,
                    $currentVersion
                );
            }
            if ($currentVersion !== null) {
                $this->resourceManager->delete($currentVersion);
            }
            $this->resourceManager->insert($preprocessedResource);
            var_dump($preprocessedResource->getUri() . " has been inserted.");
        }
        return $this->black_list;
    }

    private function handleRemovingDanglingConceptReferencesRound(Message $message)
    {
        $conceptURIs = $this->resourceManager->fetchSubjectWithPropertyGiven(
            Rdf::TYPE,
            '<' . Skos::CONCEPT . '>'
        );
        $set = $this->resourceManager->fetchByUri($message->getSetUri(), Dcmi::DATASET);
        $tenantUris = $set->getProperty(DcTerms::PUBLISHER);
        if (count($tenantUris) < 1) {
            throw new Exception2("The set " . $message->getSetUri() .
                " is supplied without a proper publisher (tenant, isntitution). ");
        }
        $tenantUri = $tenantUris[0]->getUri();
        $tenant = $this->resourceManager->fetchByUri($tenantUri, Tenant::TYPE);

        foreach ($conceptURIs as $uri) {
            $currentVersion = $this->resourceManager->fetchByUri($uri, Skos::CONCEPT);
            $isForUpdates = true;
            // __construct(ResourceManager $resourceManager, $isForUpdate, $tenant,
            // $set, $referenceCheckOn, $softConceptRelationValidation,
            // LoggerInterface $logger = null)
            $validator = new ResourceValidator(
                $this->resourceManager,
                $isForUpdates,
                $tenant,
                $set,
                true,
                true
            );
            $valid = $validator->validate($currentVersion);
            $preprocessedResource = $this->removeDanglingConeptRelationReferences(
                $currentVersion,
                $validator->getDanglingReferences()
            );
            if (!$valid) {
                $this->handleNotValidResource($uri, Skos::CONCEPT, $validator, $preprocessedResource);
                continue;
            }
            $this->handleWarnings($uri, $validator);
            $preprocessedResource = $this->specialConceptImportLogic(
                $message,
                $preprocessedResource,
                $currentVersion
            );
            $this->resourceManager->insert($preprocessedResource);
            var_dump($preprocessedResource->getUri() . " has been updated.");
        }
        return $this->black_list;
    }

    private function specialConceptImportLogic($message, $concept, $currentVersion)
    {
        if ($message->getIgnoreIncomingStatus()) {
            $concept->unsetProperty(OpenSkos::STATUS);
        }

        if ($message->getToBeChecked()) {
            $concept->addProperty(OpenSkos::TOBECHECKED, new Literal(true, null, Literal::TYPE_BOOL));
        }

        if ($message->getImportedConceptStatus() &&
            (!$concept->hasProperty(OpenSkos::STATUS))
        ) {
            $concept->addProperty(OpenSkos::STATUS, new Literal($message->getImportedConceptStatus()));
        }

        $langProperties = Resource::getLanguagedProperties();
        if ($message->getFallbackLanguage()) {
            foreach ($concept->getProperties() as $predicate => $properties) {
                foreach ($properties as $property) {
                    if (in_array($predicate, $langProperties) &&
                        $property instanceof Literal && $property->getLanguage() === null) {
                        $property->setLanguage($message->getFallbackLanguage());
                    }
                }
            }
        }
        return $concept;
    }

    private function handleNotValidResource($uri, $type, $validator, $preprocessedResource)
    {
        $this->black_list[] = $uri;
        foreach ($validator->getErrorMessages() as $errorMessage) {
            var_dump($errorMessage);
            Logging::var_logger(
                "The followig resource has not been added due "
                . "to the validation error " . $errorMessage,
                $preprocessedResource->getUri(),
                '/app/'.ConfigOptions::BACKEND.ConfigOptions::ERROR_LOG
            );
        }
        var_dump($preprocessedResource->getUri() .
            " cannot not been inserted due to the validation error(s) above.");
        $this->resourceManager->delete($preprocessedResource, $type); //remove garbage - 1
        $this->resourceManager->deleteReferencesToObject($preprocessedResource); //remove garbage - 2
    }

    private function handleWarnings($uri, $validator)
    {
        foreach ($validator->getWarningMessages() as $warning) {
            var_dump($warning);
            Logging::var_logger($warning, $uri, '/app/'.ConfigOptions::BACKEND.ConfigOptions::ERROR_LOG);
        }
    }

    private function removeDanglingConeptRelationReferences($preprocessedResource, $danglings)
    {
        $properties = $preprocessedResource->getProperties();
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
                $preprocessedResource->setProperties($property, $checked_values);
            } else {
                if ($values instanceof Uri) {
                    if (in_array($values->getUri(), $danglings)) {
                        $preprocessedResource->unsetProperty($property);
                    }
                }
            }
        }
        return $preprocessedResource;
    }
}
