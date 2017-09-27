<?php

/*
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
use OpenSkos2\Set;
use OpenSkos2\ConceptManager;
use OpenSkos2\Exception\InvalidResourceException;
use OpenSkos2\Rdf\Resource;
use OpenSkos2\Rdf\ResourceManager;
use OpenSkos2\Rdf\ResourceCollection;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class Collection
{

    /**
     * @var ResourceManager
     */
    protected $resourceManager;
    
    /**
     * @var ConceptManager
     */
    protected $conceptManager;
    
    /**
     * @var Tenant
     */
    protected $tenant;
    protected $set;
    protected $isForUpdate;
    protected $referenceCheckOn;
    protected $conceptReferenceCheckOn;
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
     * @param ResourceManager $resourceManager
     * @param Tenant $tenant optional If specified - tenant specific validation can be made.
     */
    public function __construct(
        ResourceManager $resourceManager,
        Tenant $tenant,
        Set $set,
        $isForUpdate,
        $referencecheckOn,
        $conceptReferenceCheckOn = false,
        LoggerInterface $logger = null
    ) {
        if ($logger === null) {
            $logger = new NullLogger();
        }

        $this->logger = $logger;
        $this->resourceManager = $resourceManager;
        $this->tenant = $tenant;
        $this->set = $set;
        $this->isForUpdate = $isForUpdate;
        $this->conceptReferenceCheckOn = $conceptReferenceCheckOn;
        $this->referenceCheckOn = $referencecheckOn;
    }

    /**
     * @param ResourceCollection $resourceCollection
     * @param LoggerInterface $logger
     * @throws InvalidResourceException
     */
    public function validate(ResourceCollection $resourceCollection)
    {
        $errorsFound = false;
        foreach ($resourceCollection as $resource) {
            $validator = $this->getResourceValidator($resource);
            if (!$validator->validate($resource)) {
                $errorsFound = true;
                   
                $this->errorMessages[] = 'Errors for resource "' . $resource->getUri() . '" '
                    . implode(', ', $validator->getErrorMessages());
            }
        }

        if ($errorsFound) {
            return false;
        }

        return true;
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
     * Get resource validator by resource type
     * @param Resource $resource
     * @return \OpenSkos2\Validator\Resource
     */
    private function getResourceValidator($resource)
    {
        return new \OpenSkos2\Validator\Resource(
            $this->resourceManager,
            $this->isForUpdate,
            $this->tenant,
            $this->set,
            $this->referenceCheckOn,
            $this->conceptReferenceCheckOn,
            $this->logger
        );
    }
}
