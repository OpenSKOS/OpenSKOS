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

use OpenSkos2\Concept;
use OpenSkos2\ConceptScheme;
use OpenSkos2\Rdf\Resource as RdfResource;
use OpenSkos2\Rdf\ResourceManager;
use OpenSkos2\RelationType;
use OpenSkos2\Set;
use OpenSkos2\SkosCollection;
use OpenSkos2\Tenant;
use OpenSkos2\Validator\Concept\CycleBroaderAndNarrower;
use OpenSkos2\Validator\Concept\CycleInBroader;
use OpenSkos2\Validator\Concept\CycleInNarrower;
use OpenSkos2\Validator\Concept\DuplicateBroader;
use OpenSkos2\Validator\Concept\DuplicateNarrower;
use OpenSkos2\Validator\Concept\DuplicateRelated;
use OpenSkos2\Validator\Concept\InScheme;
use OpenSkos2\Validator\Concept\InSkosCollection;
use OpenSkos2\Validator\Concept\ReferencesForConceptRelations;
use OpenSkos2\Validator\Concept\RelatedToSelf;
use OpenSkos2\Validator\Concept\RequriedPrefLabel;
use OpenSkos2\Validator\Concept\SinglePrefLabel;
use OpenSkos2\Validator\Concept\SingleStatus;
use OpenSkos2\Validator\Concept\TopConceptOf;
use OpenSkos2\Validator\Concept\UniqueNotation;
use OpenSkos2\Validator\Concept\UniquePreflabelInScheme;
use OpenSkos2\Validator\Concept\UniqueUuid;
use OpenSkos2\Validator\ConceptScheme\Creator as SchemaCreator;
use OpenSkos2\Validator\ConceptScheme\Description as SchemaDescription;
use OpenSkos2\Validator\ConceptScheme\HasTopConcept as SchemaHasTopConcept;
use OpenSkos2\Validator\ConceptScheme\InSet as SchemaInSet;
use OpenSkos2\Validator\ConceptScheme\OpenskosUuid as SchemaUuid;
use OpenSkos2\Validator\ConceptScheme\Title as SchemaTitle;
use OpenSkos2\Validator\RelationType\Creator as RelationTypeCreator;
use OpenSkos2\Validator\RelationType\Description as RelationTypeDescription;
use OpenSkos2\Validator\RelationType\Title as RelationTypeTitle;
use OpenSkos2\Validator\Set\License;
use OpenSkos2\Validator\Set\OpenskosAllowOAI;
use OpenSkos2\Validator\Set\OpenskosCode as SetOpenskosCode;
use OpenSkos2\Validator\Set\OpenskosConceptBaseUri;
use OpenSkos2\Validator\Set\OpenskosOAIBaseUri;
use OpenSkos2\Validator\Set\OpenskosUuid as SetOpenskosUuid;
use OpenSkos2\Validator\Set\OpenskosWebPage;
use OpenSkos2\Validator\Set\Publisher;
use OpenSkos2\Validator\Set\Title as SetTitle;
use OpenSkos2\Validator\Set\Type as SetType;
use OpenSkos2\Validator\SkosCollection\Creator as SkosCollCreator;
use OpenSkos2\Validator\SkosCollection\Description as SkosCollDescription;
use OpenSkos2\Validator\SkosCollection\InSet as SkosCollInSet;
use OpenSkos2\Validator\SkosCollection\Member as SkosCollMember;
use OpenSkos2\Validator\SkosCollection\OpenskosUuid as SkosCollUuid;
use OpenSkos2\Validator\SkosCollection\Title as SkosCollTitle;
use OpenSkos2\Validator\Tenant\OpenskosCode;
use OpenSkos2\Validator\Tenant\OpenskosDisableSearchInOtherTenants;
use OpenSkos2\Validator\Tenant\OpenskosEnableStatussesSystem;
use OpenSkos2\Validator\Tenant\OpenskosUuid;
use OpenSkos2\Validator\Tenant\Type;
use OpenSkos2\Validator\Tenant\VCardAdress;
use OpenSkos2\Validator\Tenant\VCardEmail;
use OpenSkos2\Validator\Tenant\VCardOrg;
use OpenSkos2\Validator\Tenant\VCardUrl;
use OpenSkos2\Validator\Concept\DisjointXlLabels;
use OpenSkos2\Validator\Concept\LanguageRequired;
use OpenSkos2\Validator\Concept\NoEmptyValues;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class Resource
{

    /**
     * @var ResourceManager
     */
    protected $resourceManager;

    /**
     * @var boolean
     */
    protected $isForUpdate;
    protected $referenceCheckOn;
    protected $conceptReferenceCheckOn;
    protected $tenant;
    protected $set;

    /**
     * Holds all error messages
     *
     * @var array
     */
    private $errorMessages = [];

    /**
     * Holds all warning messages
     *
     * @var array
     */
    private $warningMessages = [];

    /**
     * Holds all dangling references
     *
     * @var array
     */
    private $danglingReferences = [];

    /**
     * Logger
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ResourceManager          $resourceManager
     * @param Tenant                   $tenant optional If specified - tenant specific validation can be made.
     * @param LoggerInterface $logger
     */
    
    
    public function __construct(
        ResourceManager $resourceManager,
        $tenant,
        $set,
        $isForUpdate,
        $referenceCheckOn,
        $conceptReferenceCheckOn=true,
        LoggerInterface $logger = null
    ) {
        if ($logger === null) {
            $this->logger = new NullLogger();
        } else {
            $this->logger = $logger;
        }

        $this->resourceManager = $resourceManager;
        $this->isForUpdate = $isForUpdate;
        $this->tenant = $tenant;
        $this->set = $set;
        $this->referenceCheckOn = $referenceCheckOn;
        $this->conceptReferenceCheckOn = $conceptReferenceCheckOn;
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
     * Get warning messages
     *
     * @return array
     */
    public function getWarningMessages()
    {
        return $this->warningMessages;
    }

    /**
     * @return string[]
     */
    public function getDanglingReferences()
    {

        return $this->danglingReferences;
    }

    /**
     * Apply the validators to the resource.
     * @param RdfResource $resource
     * @return boolean True if validators are not failing
     */
    protected function applyValidators(RdfResource $resource)
    {
        $errorsFound = false;
        /** @var ValidatorInterface $validator */
        foreach ($this->getValidators($resource) as $validator) {
            $valid = $validator->validate($resource);
            if ($valid) {
                continue;
            }
            foreach ($validator->getErrorMessages() as $message) {
                $this->errorMessages[] = $message;
            }
            foreach ($validator->getWarningMessages() as $message) {
                $this->warningMessages[] = "Validator's WARNING: $message. \n";
            }
            foreach ($validator->getDanglingReferences() as $ref) {
                $this->danglingReferences[] = $ref;
            }
            $this->logger->error('Errors founds while validating resource "' . $resource->getUri() . '"');
            $this->logger->error(implode(', ', $validator->getErrorMessages()));

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
        if ($resource instanceof Concept) {
            return $this->getConceptValidators();
        }
        if ($resource instanceof ConceptScheme) {
            return $this->getSchemaValidators();
        }
        if ($resource instanceof SkosCollection) {
            return $this->getSkosCollectionValidators();
        }
        if ($resource instanceof Set) {
            return $this->getSetValidators();
        }
        if ($resource instanceof Tenant) {
            return $this->getTenantValidators();
        }
        if ($resource instanceof RelationType) {
            return $this->getRelationTypeValidators();
        }
    }

    /**
     * Return all validators for a schema or Skos:collection
     * @return ResourceValidator[]
     */
    private function getSchemaValidators()
    {
        $validators = [
            new SchemaInSet($this->referenceCheckOn),
            new SchemaTitle(),
            new SchemaDescription(),
            new SchemaCreator($this->referenceCheckOn),
            new SchemaUuid(),
            new SchemaHasTopConcept($this->referenceCheckOn, $this->conceptReferenceCheckOn)
        ];
        $validators = $this->refineValidators($validators);
        return $validators;
    }

    private function getSkosCollectionValidators()
    {
        $validators = [
            new SkosCollInSet($this->referenceCheckOn),
            new SkosCollTitle(),
            new SkosCollDescription(),
            new SkosCollCreator($this->referenceCheckOn),
            new SkosCollUuid(),
            new SkosCollMember($this->referenceCheckOn, $this->conceptReferenceCheckOn)
        ];
        $validators = $this->refineValidators($validators);
        return $validators;
    }

    private function getRelationTypeValidators()
    {
        $validators = [
            new RelationTypeTitle(),
            new RelationTypeDescription(),
            new RelationTypeCreator($this->referenceCheckOn)
        ];
        $validators = $this->refineValidators($validators);
        return $validators;
    }

    private function getSetValidators()
    {
        $validators = [
            new License(),
            new OpenskosAllowOAI(),
            new SetOpenskosCode(),
            new OpenskosConceptBaseUri(),
            new SetOpenskosUuid(),
            new OpenskosOAIBaseUri(),
            new OpenskosWebPage(),
            new Publisher($this->referenceCheckOn),
            new SetTitle(),
            new SetType()
        ];
        $validators = $this->refineValidators($validators);
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
            new VCardAdress(),
            new VCardEmail(),
            new VCardUrl(),
            new VCardOrg()
        ];
        $validators = $this->refineValidators($validators);
        return $validators;
    }

    private function getConceptValidators()
    {
        $validators = [
            new InScheme($this->referenceCheckOn),
            new InSkosCollection($this->referenceCheckOn),
            new SingleStatus(),
            new NoEmptyValues(),
            new LanguageRequired(),
            new SinglePrefLabel(),
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
            new TopConceptOf($this->referenceCheckOn),
            new ReferencesForConceptRelations($this->referenceCheckOn, $this->conceptReferenceCheckOn),
            new DisjointXlLabels(),
        ];
        $validators = $this->refineValidators($validators);
        return $validators;
    }

    private function refineValidators($validators)
    {
        foreach ($validators as $validator) {
            $validator->setResourceManager($this->resourceManager);
            $validator->setFlagIsForUpdate($this->isForUpdate);
            $validator->setTenant($this->tenant);
            $validator->setSet($this->set);
        }
        return $validators;
    }
}
