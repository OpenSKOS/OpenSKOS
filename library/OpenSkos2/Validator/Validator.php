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
use OpenSkos2\Exception\InvalidResourceException;
use OpenSkos2\Rdf\Resource;
use OpenSkos2\Rdf\ResourceCollection;
use OpenSkos2\Validator\Concept\DuplicateBroader;
use OpenSkos2\Validator\Concept\DuplicateNarrower;
use OpenSkos2\Validator\Concept\DuplicateRelated;
use OpenSkos2\Validator\Concept\InScheme;
use OpenSkos2\Validator\Concept\RelatedToSelf;
use OpenSkos2\Validator\Concept\UniqueNotation;
use OpenSkos2\Validator\Concept\UniqueNotationInTenant;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class Validator
{
    /**
     * @return ResourceValidator[]
     */
    public function getDefaultValidators()
    {
        return [
            new DuplicateBroader(),
            new DuplicateNarrower(),
            new DuplicateRelated(),
            new InScheme(),
            new RelatedToSelf(),
            new UniqueNotation()
        ];
    }
    
    /**
     * @var Tenant
     */
    protected $tenant;
    
    /**
     * @param Tenant $tenant optional If specified - tenant specific validation can be made.
     */
    public function __construct(Tenant $tenant = null)
    {
        $this->tenant = $tenant;
    }

    /**
     * @param ResourceCollection $resourceCollection
     * @param LoggerInterface $logger
     * @throws InvalidResourceException
     */
    public function validateCollection(ResourceCollection $resourceCollection, LoggerInterface $logger = null)
    {
        if ($logger === null) {
            $logger = new NullLogger();
        }

        $errorsFound = false;
        foreach ($resourceCollection as $resource) {
            $errorsFound = $errorsFound || (!$this->applyValidators($resource, $logger));
        }

        if ($errorsFound) {
            throw new InvalidResourceException("Invalid resource(s) found");
        }
    }

    /**
     * @param Resource $resource
     * @param LoggerInterface $logger
     * @throws InvalidResourceException
     */
    public function validateResource(Resource $resource, LoggerInterface $logger = null)
    {
        if ($logger === null) {
            $logger = new NullLogger();
        }

        if (!$this->applyValidators($resource, $logger)) {
            throw new InvalidResourceException("Invalid resource(s) found");
        }
    }
    
    /**
     * Apply the validators to the resource.
     * @param Resource $resource
     * @param LoggerInterface $logger
     * @return boolean True if validators are not failing
     */
    protected function applyValidators(Resource $resource, LoggerInterface $logger)
    {
        $errorsFound = false;
        foreach ($this->getValidatorsList() as $validator) {
            $valid = $validator->validate($resource);
            if (!$valid) {
                $logger->error("Errors founds while validating resource " . $resource->getUri());
                $logger->error($validator->getErrorMessage());
                $errorsFound = true;
            }
        }
        return !$errorsFound;
    }
    
    /**
     * Gets validators. Adds tenant specific validators if any.
     * @return ResourceValidator[]
     */
    protected function getValidatorsList()
    {
        $validators = $this->getDefaultValidators();
        
        if (!empty($this->tenant)) {
            if ($this->tenant->getRequireUniqueNotation()) {
                $validators[] = new UniqueNotationInTenant($this->tenant);
            }
        }
        
        return $validators;
    }
}
