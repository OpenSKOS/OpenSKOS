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
        return [];
    }
    
    /**
     * Return all validators for a concept
     * @return ResourceValidator[]
     */
    private function getConceptValidators()
    {
        $validators = [
            new DuplicateBroader(),
            new DuplicateNarrower(),
            new DuplicateRelated(),
            new CycleBroaderAndNarrower(),
            new CycleInBroader(),
            new CycleInNarrower(),
            new InScheme(),
            new RelatedToSelf(),
            new UniqueNotation(),
            new RequriedPrefLabel(),
            new UniquePreflabelInScheme(),
            new UniqueUuid(),
        ];
        
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
