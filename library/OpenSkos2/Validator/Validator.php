<?php
/**
 * Created by PhpStorm.
 * User: jsmit
 * Date: 27/08/2015
 * Time: 16:45
 */

namespace OpenSkos2\Validator;


use OpenSkos2\Exception\InvalidResourceException;
use OpenSkos2\Rdf\Resource;
use OpenSkos2\Rdf\ResourceCollection;
use OpenSkos2\Validator\Concept\DuplicateBroader;
use OpenSkos2\Validator\Concept\DuplicateNarrower;
use OpenSkos2\Validator\Concept\DuplicateRelated;
use OpenSkos2\Validator\Concept\InScheme;
use OpenSkos2\Validator\Concept\RelatedToSelf;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class Validator
{
    /**
     * @return ResourceValidator[]
     */
    public function getDefaultValidators(){
        return [
            new DuplicateBroader(),
            new DuplicateNarrower(),
            new DuplicateRelated(),
            new InScheme(),
            new RelatedToSelf(),
        ];
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
            foreach ($this->getDefaultValidators() as $validator) {
                $valid = $validator->validate($resource);
                if (!$valid) {
                    $logger->error("Errors founds while validating resource " . $resource->getUri());
                    $logger->error($validator->getErrorMessage());
                    $errorsFound = true;
                }
            }
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

        $errorsFound = false;
        foreach ($this->getDefaultValidators() as $validator) {
            $valid = $validator->validate($resource);
            if (!$valid) {
                $logger->error("Errors founds while validating resource " . $resource->getUri());
                $logger->error($validator->getErrorMessage());
                $errorsFound = true;
            }
        }

        if ($errorsFound) {
            throw new InvalidResourceException("Invalid resource(s) found");
        }
    }
}