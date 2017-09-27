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
use OpenSkos2\Set;
use OpenSkos2\Converter\File;
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Rdf\ResourceManager;
use OpenSkos2\ConceptManager;
use OpenSkos2\PersonManager;
use OpenSkos2\Tenant;
use OpenSkos2\Import\Command\CollectionHelper;
use OpenSkos2\Validator\Resource as ResourceValidator;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

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
     * @var PersonManager
     */
    private $personManager;

    /**
     * @var Tenant
     */
    protected $tenant;

    /**
     * @var Set
     */
    private $set;

    /**
     * Command constructor.
     * @param ResourceManager $resourceManager
     * @param ConceptManager $conceptManager
     * @param PersonManager $personManager
     * @param Tenant $tenant optional If specified - tenant specific validation can be made.
     */
    public function __construct(
        ResourceManager $resourceManager,
        ConceptManager $conceptManager,
        PersonManager $personManager,
        Tenant $tenant = null
    ) {
    


        $this->resourceManager = $resourceManager;
        $this->conceptManager = $conceptManager;
        $this->personManager = $personManager;
        $this->tenant = $tenant;
    }

    public function handle(Message $message)
    {
        $file = new File($message->getFile());
        $resourceCollection = $file->getResources(null, Concept::$classes['SkosXlLabels']);

        $this->set = $this->resourceManager->fetchByUri($message->getSetUri(), Set::TYPE);

        // Disable commit's for every concept
        $this->conceptManager->setIsNoCommitMode(true);

        $helper = new CollectionHelper(
            $this->resourceManager,
            $this->conceptManager,
            $this->personManager,
            $this->tenant,
            $message
        );
        $helper->setLogger($this->logger);
        $helper->prepare($resourceCollection);

        /* Constructor for Validator
         * public function __construct(
          ResourceManager $resourceManager,
          $tenant,
          $set,
          $isForUpdate,
          $referenceCheckOn,
          $conceptReferenceCheckOn = true,
          LoggerInterface $logger = null */



        $validator = new ResourceValidator(
            $this->conceptManager,
            $this->tenant,
            $this->set,
            !($message->getNoUpdates()),
            false,
            false,
            $this->logger
        );


        // validation is in the loop below per resource, not with the whole bunch
        /* if (!$validator->validate($resourceCollection)) {
          throw new \Exception('Failed validation: ' . PHP_EOL . implode(PHP_EOL, $validator->getErrorMessages()));
          } */

        if ($message->getClearSet()) {
            $this->conceptManager->deleteBy([\OpenSkos2\Namespaces\OpenSkos::SET => $message->getSetUri()]);
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
                $this->conceptManager->deleteBy([Skos::INSCHEME => $scheme]);
                $this->resourceManager->delete($scheme);
            }
        }

        foreach ($resourceCollection as $resourceToInsert) {
            if ($validator->validate($resourceToInsert)) {
                if ($resourceToInsert instanceof Concept) {
                    $this->conceptManager->replace($resourceToInsert);
                    $this->logger->info("inserted concept {$resourceToInsert->getUri()}");
                } else {
                    $this->resourceManager->replace($resourceToInsert);
                    $this->logger->info("inserted resource {$resourceToInsert->getUri()}");
                }
            } else {
                $this->logger->error("Resource {$resourceToInsert->getUri()}: \n" .
                    implode(' , ', $validator->getErrorMessages()));
            }
            if (count($validator->getWarningMessages()) > 0) {
                $this->logger->warning("Resource {$resourceToInsert->getUri()}:\n" .
                    implode(' , ', $validator->getWarningMessages()));
            }
            if (count($validator->getDanglingReferences()) > 0) {
                $this->logger->warning("Dangling references for resource {$resourceToInsert->getUri()}:\n" .
                    implode(' , ', $validator->getDanglingReferences()));
            }
        }
        // Commit all solr documents
        $this->conceptManager->commit();

        if ($message->removeDanglingReferences()) {
            // Removing dangling references run
            $this->logger->info("...");
            $this->logger->info("Removing danglig references");
            $this->logger->info("...");
            $validatorUpdate = new ResourceValidator(
                $this->conceptManager,
                $this->tenant,
                $this->set,
                true,
                true,
                true,
                $this->logger
            );
            foreach ($resourceCollection as $resourceInserted) {
                $uri = $resourceInserted->getUri();
                $type = $resourceInserted->getType()->getUri();
                try {
                    $resource = $this->resourceManager->fetchByUri($uri, $type);
                    $resource = $this->removeDanglingReferences($resource, $validator->getDanglingReferences());
                    if ($validatorUpdate->validate($resource)) {
                        $this->conceptManager->replace($resourceToInsert);
                        $this->logger->info("replaced resource {$resource->getUri()}");
                    } else {
                        $this->logger->error("Resource {$resource->getUri()} of "
                            . "type {$resource->getType()->getUri()}: \n" .
                            implode(' , ', $validator->getErrorMessages()));
                    }
                } catch (\OpenSkos2\Exception\ResourceNotFoundException $ex) {
                    $this->logger->info("Skipping invalid resource {$uri}");
                }
            }
        }
    }

    // validating and inserting concepts chunk by chunk
    public function handleQuick(Message $message, $bulksize)
    {
        $file = new File($message->getFile());
        $resourceCollection = $file->getResources(Concept::TYPE, Concept::$classes['SkosXlLabels']);

        $this->set = $this->resourceManager->fetchByUri($message->getSetUri(), Set::TYPE);

        // Disable commit's for every concept
        $this->conceptManager->setIsNoCommitMode(true);

        $helper = new CollectionHelper(
            $this->resourceManager,
            $this->conceptManager,
            $this->personManager,
            $this->tenant,
            $message
        );
        $helper->setLogger($this->logger);
        $helper->prepare($resourceCollection);

        if ($message->getClearSet()) {
            $this->conceptManager->deleteBy([\OpenSkos2\Namespaces\OpenSkos::SET => $message->getSetUri()]);
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
                $this->conceptManager->deleteBy([Skos::INSCHEME => $scheme]);
                $this->resourceManager->delete($scheme);
            }
        }

        /* Constructor for Validator
         * public function __construct(
          ResourceManager $resourceManager,
          $tenant,
          $set,
          $isForUpdate,
          $referenceCheckOn,
          $conceptReferenceCheckOn = true,
          LoggerInterface $logger = null */



        $validator = new ResourceValidator(
            $this->conceptManager,
            $this->tenant,
            $this->set,
            !($message->getNoUpdates()),
            false,
            false,
            $this->logger
        );


        $finish = count($resourceCollection); // size of the whole collection
        $n_bulks = $this->intdiv($finish, $bulksize);
        for ($i = 0; $i < $n_bulks + 1; $i++) {
            $current_finish = min($i * $bulksize + $bulksize, $finish);
            
                
            // validate (validate collection amounts to validate per resource anyway, see /Validator/Collection)
            $valid = true;
            $concepts = array($current_finish - $i * $bulksize);
            $k=0;
            for ($j = $i * $bulksize; $j < $current_finish; $j++) {
                $concept = $resourceCollection->offsetGet($j);
                $valid = $validator->validate($concept);
                if (!$valid) {
                    $this->logger->error(implode(' , ', $validator->getErrorMessages()));
                } else {
                    $concepts[$k] = $concept;
                    $k++;
                }
                if (count($validator->getWarningMessages()) > 0) {
                    $this->logger->warning(implode(' , ', $validator->getWarningMessages()));
                }
                if (count($validator->getDanglingReferences()) > 0) {
                    $this->logger->warning(implode(' , ', $validator->getDanglingReferences()));
                }
            }
            
            if ($valid) {
                $subcollection = new \OpenSkos2\ConceptCollection($concepts);
                $this->conceptManager->insertCollection($subcollection);
                var_dump("inserted bulk # ". ($i+1));
            }
        }
        // Commit all solr documents
        $this->conceptManager->commit();
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
    
    // works only for nonnegative numbers
    // implemented in PHP 7
    private function intdiv($dividend, $divisor)
    {
        for ($i=0; $i<=$divisor; $i++) {
            if (($i+1)*$divisor>$dividend) {
                return $i;
            }
        }
         return -1;
    }
}
