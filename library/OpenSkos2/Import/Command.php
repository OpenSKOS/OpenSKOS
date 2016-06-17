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
use OpenSkos2\Converter\File;
use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Rdf\Literal;
use OpenSkos2\Rdf\Uri;
use OpenSkos2\Rdf\ResourceManager;
use OpenSkos2\ConceptManager;
use OpenSkos2\Tenant;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Rhumsaa\Uuid\Uuid;

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

        // @TODO Most of the code below has to be applied for the api and for the api,
        // so has to be moved to a shared place.
        
        
        // Stuff needed before validation.
        foreach ($resourceCollection as $resourceToInsert) {
            // Concept only logic
            // Generate uri if none or blank (_:genid<n>) is given.
            if ($resourceToInsert instanceof Concept) {
                $setUri = $message->getSetUri();
                
                
                $resourceToInsert->addProperty(\OpenSkos2\Namespaces\OpenSkos::SET, $setUri);
                
                if ($resourceToInsert->isBlankNode()) {
                    $params['seturi']=$setUri->getURi();
                    $params['type']=Skos::CONCEPT;
                    $notations = $resourceToInsert ->getProperty(Skos::NOTATION);
                    if (count($notations)<0) {
                       $params['notation'] = null; 
                    } else {
                       $params['notation'] = $notations[0];
                    }
                    $resourceToInsert->selfGenerateUri($this->conceptManager, $params);
                }
                
                $uuids = $resourceToInsert->getProperty(\OpenSkos2\Namespaces\OpenSkos::UUID);
                if (count($uuids) < 1) {
                    $uuid = Uuid::uuid4();
                    $resourceToInsert->addProperty(\OpenSkos2\Namespaces\OpenSkos::UUID, new Literal($uuid));
                };
            }
        }
        
        $validator = new \OpenSkos2\Validator\Collection($this->resourceManager, $this->tenant);
        if (!$validator->validate($resourceCollection, $this->logger)) {
            var_dump($validator->getErrorMessages());
            throw new \Exception("\n Failed validation \n");
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
                $userUri = $message->getUser()->getUri(); 
                $tenantUri = $this->resourceManager->fetchUsersInstitution($userUri);
                $resourceToInsert = $this->ensureMetadata(
                    $resourceToInsert,
                    new Uri($tenantUri),
                    new Uri($message->getSetUri()),
                    new Uri($userUri),
                    $currentVersion ? $currentVersion->getStatus(): null
                );
            }
            
            if (isset($currentVersions[$resourceToInsert->getUri()])) {
                $this->resourceManager->delete($currentVersions[$resourceToInsert->getUri()]);
            }
            $this->resourceManager->insert($resourceToInsert);
        }
    }
    
 private function ensureMetadata($concept, Uri $tenantUri, Uri $set, Uri $person, $oldStatus = null)
    {
        $nowLiteral = function () {
            return new Literal(date('c'), null, \OpenSkos2\Rdf\Literal::TYPE_DATETIME);
        };
        
        $forFirstTimeInOpenSkos = [
            OpenSkos::UUID => new Literal(Uuid::uuid4()),
            OpenSkos::TENANT => $tenantUri,
            OpenSkos::SET => $set,
            DcTerms::CREATOR => $person,
            DcTerms::DATESUBMITTED => $nowLiteral(),
        ];
        
        foreach ($forFirstTimeInOpenSkos as $property => $defaultValue) {
            if (!$concept->hasProperty($property)) {
                $concept->setProperty($property, $defaultValue);
            }
        }
        
        // @TODO Should we add modified instead of replace it.
        $concept->setProperty(DcTerms::MODIFIED, $nowLiteral());
        $concept->addUniqueProperty(DcTerms::CONTRIBUTOR, $person);
        
        // Status is updated
        
        if ($oldStatus != $concept->getStatus()) {
            $concept->unsetProperty(DcTerms::DATEACCEPTED);
            $concept->unsetProperty(OpenSkos::ACCEPTEDBY);
            $concept->unsetProperty(OpenSkos::DATE_DELETED);
            $concept->unsetProperty(OpenSkos::DELETEDBY);

            switch ($concept->getStatus()) {
                case \OpenSkos2\Concept::STATUS_APPROVED:
                    $concept->addProperty(DcTerms::DATEACCEPTED, $nowLiteral());
                    $concept->addProperty(OpenSkos::ACCEPTEDBY, $person);
                    break;
                case \OpenSkos2\Concept::STATUS_DELETED:
                    $concept->addProperty(OpenSkos::DATE_DELETED, $nowLiteral());
                    $concept->addProperty(OpenSkos::DELETEDBY, $person);
                    break;
            }
        }
        return $concept;
    }
}
