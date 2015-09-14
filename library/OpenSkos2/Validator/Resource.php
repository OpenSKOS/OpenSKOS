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
use OpenSkos2\Rdf\Resource as RdfResource;
use OpenSkos2\Rdf\ResourceManager;
use OpenSkos2\Rdf\ResourceCollection;
use OpenSkos2\Validator\Concept\DuplicateBroader;
use OpenSkos2\Validator\Concept\DuplicateNarrower;
use OpenSkos2\Validator\Concept\DuplicateRelated;
use OpenSkos2\Validator\Concept\InScheme;
use OpenSkos2\Validator\Concept\RelatedToSelf;
use OpenSkos2\Validator\Concept\UniqueNotation;
use OpenSkos2\Validator\Concept\UniqueNotationInTenant;
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
     * @param ResourceManager $resourceManager
     * @param Tenant $tenant optional If specified - tenant specific validation can be made.
     */
    public function __construct(ResourceManager $resourceManager, Tenant $tenant = null)
    {
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
        foreach ($this->createValidators() as $validator) {
            $valid = $validator->validate($resource);
            if (!$valid) {
                foreach ($validator->getErrorMessages() as $message) {
                    $this->errorMessages[] = $message;
                }

                $errorsFound = true;
            }
        }
        return !$errorsFound;
    }

    /**
     * @return ValidatorInterface[]
     */
    private function createValidators()
    {
        $validators = [
            new DuplicateBroader(),
            new DuplicateNarrower(),
            new DuplicateRelated(),
            new InScheme(),
            new RelatedToSelf(),
            new UniqueNotation()
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
