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
use OpenSkos2\Converter\File;
use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Namespaces\Dcmi;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Namespaces\Rdf;
use OpenSkos2\Rdf\Literal;
use OpenSkos2\Rdf\ResourceManager;
use OpenSkos2\Tenant;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use OpenSkos2\Validator\Resource as ResourceValidator;
use OpenSkos2\Preprocessor;

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
    public function __construct(
        ResourceManager $resourceManager,
        Tenant $tenant = null
    ) {
        $this->resourceManager = $resourceManager;
        $this->tenant = $tenant;
    }


    public function handle(Message $message)
    {
        $file = new File($message->getFile());
        $resourceCollection = $file->getResources();
        
        $set = $this->resourceManager->fetchByUri($message->getSetUri(), Dcmi::DATASET);
        $tenantUris = $set ->getProperty(DcTerms::PUBLISHER);
        if (count($tenantUris)<1) {
            throw new Exception("The set " . $message->getSetUri() . " is supplied without a proper publisher (tenant, isntitution). ");
        };       
        $tenantUri = $tenantUris[0]->getUri();
        
        //** Some purging stuff from the original picturae code,
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
                $this->resourceManager->delete($scheme, Skos::CONCEPTSCHEME);
            }
        }
        // ***
       
        foreach ($resourceCollection as $resourceToInsert) {
            $params['seturi'] = $message->getSetUri();
            $uri = $resourceToInsert->getUri();
            
            $types = $resourceToInsert->getProperty(Rdf::TYPE);
            if (count($types)<1) {
               throw new \Exception("The resource " . $uri . " does not have rdf-type. "); 
            }
            $type=$types[0]->getUri();
            $preprocessor = new Preprocessor($this->resourceManager, $type, $message->getUser()->getUri());

            $exists = $this->resourceManager->countTriples("<".$uri.">","<".Rdf::TYPE.">", "<".$type.">");
            if($exists>0) {
                $currentVersion = $this->resourceManager->fetchByUri($uri, $type);
                if ($message->getNoUpdates()) {
                    var_dump("Skipping resource {$uri}, because it already exists and NoUpdates is set to true. ");
                    $this->logger->warning("Skipping resource {$uri}, because it already exists and NoUpdates is set to true.");
                    continue;
                } else {
                    $preprocessedResource = $preprocessor->forUpdate($resourceToInsert, $params);
                    $isForUpdates = true;

                    if ($currentVersion->hasProperty(DcTerms::DATESUBMITTED)) {
                        $dateSubm = $currentVersion->getProperty(DcTerms::DATESUBMITTED);
                        $preprocessedResource->unsetProperty(DcTerms::DATESUBMITTED);
                        $preprocessedResource->setProperty(DcTerms::DATESUBMITTED, $dateSubm[0]);
                    }
                }
            } else { // adding a new resource
                $autoGenerateIdentifiers = true;
                $preprocessedResource = $preprocessor->forCreation($resourceToInsert, $params, $autoGenerateIdentifiers);
                $isForUpdates = false;
                $currentVersion = null;
            }

            $validator = new ResourceValidator($this->resourceManager, $isForUpdates, $tenantUri, false);
            $valid = $validator->validate($preprocessedResource);
            if (!$valid) {
                foreach ($validator->getErrorMessages() as $errorMessage) {
                    var_dump($errorMessage);
                }
                var_dump($preprocessedResource->getUri() . " cannot not been inserted due to the validation error(s) above.");
                continue;
            }

            if ($preprocessedResource instanceof Concept) {
                $preprocessedResource = $this->specialConceptImportLogic($message, $preprocessedResource, $currentVersion);
            }

            if ($currentVersion !== null) {
                $this->resourceManager->delete($currentVersion);
            }
            $this->resourceManager->insert($preprocessedResource);
            var_dump($preprocessedResource->getUri() . " has been inserted.");
        }
    }

    function specialConceptImportLogic($message, $concept, $currentVersion) {
        if ($message->getIgnoreIncomingStatus()) {
            $concept->unsetProperty(OpenSkos::STATUS);
        }

        if ($message->getToBeChecked()) {
            $concept->addProperty(OpenSkos::TOBECHECKED, new Literal(true, null, Literal::TYPE_BOOL));
        }

        if ($message->getImportedConceptStatus() &&
                (!$concept->hasProperty(OpenSkos::STATUS))
        ) {
            $concept->addProperty(OpenSkos::STATUS, new Literal($message->getImportedConceptStatus())
            );
        }

        // @TODO Those properties has to have types, rather then ignoring them from a list
        $nonLangProperties = [Skos::NOTATION, OpenSkos::TENANT, OpenSkos::STATUS, OpenSkos::UUID, OpenSkos::CODE];
        if ($message->getFallbackLanguage()) {
            foreach ($concept->getProperties() as $predicate => $properties) {
                foreach ($properties as $property) {
                    if (!in_array($predicate, $nonLangProperties) && $property instanceof Literal && $property->getType() === null && $property->getLanguage() === null) {
                        $property->setLanguage($message->getFallbackLanguage());
                    }
                }
            }
        }
        return $concept;
    }
    
}
