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

namespace OpenSkos2\Validator;

use OpenSkos2\Tenant;
use OpenSkos2\Rdf\Resource as RdfResource;
use OpenSkos2\Rdf\ResourceManager;
use OpenSkos2\Validator\Concept\CycleBroaderAndNarrower;
use OpenSkos2\Validator\Concept\CycleInBroader;
use OpenSkos2\Validator\Concept\CycleInNarrower;
use OpenSkos2\Validator\Concept\DuplicateBroader;
use OpenSkos2\Validator\Concept\DuplicateNarrower;
use OpenSkos2\Validator\Concept\DuplicateRelated;
use OpenSkos2\Validator\Concept\InScheme;
use OpenSkos2\Validator\Concept\RelatedToSelf;
use OpenSkos2\Validator\Concept\UniqueNotation;
use OpenSkos2\Validator\Concept\RequriedPrefLabel;
use OpenSkos2\Validator\Concept\UniquePreflabelInScheme;
use OpenSkos2\Validator\Concept\UniqueUuid;
use OpenSkos2\Validator\DependencyAware\ResourceManagerAware;
use OpenSkos2\Validator\DependencyAware\TenantAware;

use OpenSkos2\Validator\Tenant\OpenskosCode;
use OpenSkos2\Validator\Tenant\OpenskosUuid;
use OpenSkos2\Validator\Tenant\OpenskosDisableSearchInOtherTenants;
use OpenSkos2\Validator\Tenant\Type;
use OpenSkos2\Validator\Tenant\OpenskosEnableStatussesSystem;
use OpenSkos2\Validator\Tenant\vCardAdress;
use OpenSkos2\Validator\Tenant\vCardEmail;
use OpenSkos2\Validator\Tenant\vCardURL;
use OpenSkos2\Validator\Tenant\vCardOrg;


use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class Resource
{
    /**
     * @var ResourceManager
     */
    protected $resourceManager;

    /**
     * @var Tenant
     */
    protected $tenant;

    /**
     * Holds all error messages
     *
     * @var array
     */
    private $errorMessages = [];

    /**
     * Logger
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ResourceManager          $resourceManager
     * @param Tenant                   $tenant optional If specified - tenant specific validation can be made.
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(ResourceManager $resourceManager, Tenant $tenant = null, LoggerInterface $logger = null)
    {
        if ($logger === null) {
            $this->logger = new NullLogger();
        } else {
            $this->logger = $logger;
        }
        
        $this->resourceManager = $resourceManager;
        $this->tenant = $tenant;
    }

    /**
     * Validate the resource
     *
     * @param RdfResource $resource
     * @return boolean
     */
    public function validate(RdfResource $resource)
    {
        return $this->applyValidators($resource);
    }

    /**
     * Get error messages
     *
     * @return array
     */
    public function getErrorMessages()
    {
        return $this->errorMessages;
    }

    /**
     * Apply the validators to the resource.
     * @param RdfResource $resource
     * @return boolean True if validators are not failing
     */
    protected function applyValidators(RdfResource $resource)
    {
        $errorsFound = false;
        /** @var \OpenSkos2\Validator\ValidatorInterface $validator */
        foreach ($this->getValidators($resource) as $validator) {
            $valid = $validator->validate($resource);
            if ($valid) {
                continue;
            }

            foreach ($validator->getErrorMessages() as $message) {
                $this->errorMessages[] = $message;
            }
            
            $this->logger->error('Errors founds while validating resource ' . $resource->getUri());
            $this->logger->error($validator->getErrorMessages());
            
            $errorsFound = true;
        }

        return !$errorsFound;
    }

    /**
     * Get validators based on the type of resource
     *
     * @param RdfResource $resource
     * @return array
     */
    private function getValidators(RdfResource $resource)
    {
        if ($resource instanceof \OpenSkos2\Concept) {
            return $this->getConceptValidators();
        }
        if ($resource instanceof \OpenSkos2\Schema) {
            return $this->getSchemaValidators();
        }
        if ($resource instanceof \OpenSkos2\SkosCollection) {
            return $this->getSkosCollectionValidators();
        }
        if ($resource instanceof \OpenSkos2\Set) {
            return $this->getSetValidators();
        }
        if ($resource instanceof \OpenSkos2\Tenant) {
            return $this->getTenantValidators();
        }
        return [];
    }
    
    /**
     * Return all validators for a schema or Skos:collection
     * @return ResourceValidator[]
     */
    private function getSchemaValidators()
    {
        $validators = [
            new Schema\InSet(),
            new Schema\Title(),
            new Schema\Desciprion(),
            new Schema\Creator(),
        ];
        $validators = $this -> refineValidators($validators);
        return $validators;
    }
    
    private function getSkosCollectionValidators()
    {
        $validators = [
            new SkosCollection\InSet(),
            new SkosCollection\Title(),
            new SkosCollection\Desciprion(),
            new SkosCollection\Creator(),
        ];
        $validators = $this -> refineValidators($validators);
        return $validators;
    }
    
    private function getSetValidators()
    {
        $validators = [
            new Set\License(),
            new Set\OpenskosAllowOAI(),
            new Set\OpenskosCode(),
            new Set\OpenskosConceptBaseUri(),
            new Set\OpenskosUuid(),
            new Set\OpenskosOAIBaseUri(),
            new Set\OpenskosWebPage(),
            new Set\Publisher(),
            new Set\Title(),
            new Set\Type()
        ];
        $validators = $this -> refineValidators($validators);
        return $validators;
    }
    
    private function getTenantValidators()
    {
        $validators = [
            new OpenskosCode(),
            new OpenskosUuid(),
            new Type(),
            new OpenskosDisableSearchInOtherTenants(),
            new OpenskosEnableStatussesSystem(),
            new vCardAdress(),
            new vCardEmail(),
            new vCardURL(),
            new vCardOrg()
        ];
        $validators = $this -> refineValidators($validators);
        return $validators;
    }
    
    private function getConceptValidators()
    {
        $validators = [
            new InScheme(),
            new InSkosCollection(),
            new InSet(),
            new UniqueNotation(),
            new RequriedPrefLabel(),
            new UniquePreflabelInScheme(),
            new UniqueUuid(),
            new DuplicateBroader(),
            new DuplicateNarrower(),
            new DuplicateRelated(),
            new CycleBroaderAndNarrower(),
            new CycleInBroader(),
            new CycleInNarrower(),
            new RelatedToSelf(),
        ];
        $validators = $this -> refineValidators($validators);
        return $validators;
    }
    
    private function refineValidators($validators){
        
        foreach ($validators as $validator) {
            if ($validator instanceof ResourceManagerAware) {
                $validator->setResourceManager($this->resourceManager);
            }
            if ($validator instanceof TenantAware && $this->tenant !== null) {
                $validator->setTenant($this->tenant);
            }
        }
        
        return $validators;
    }
}
