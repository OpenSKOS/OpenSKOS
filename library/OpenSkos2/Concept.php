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

namespace OpenSkos2;

use Exception;
use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Namespaces\Rdf;
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Namespaces\SkosXl;
use OpenSkos2\Rdf\Literal;
use OpenSkos2\Rdf\Resource;
use OpenSkos2\Rdf\Uri;
use OpenSkos2\Custom\UriGeneration;
use OpenSkos2\PersonManager;
use Rhumsaa\Uuid\Uuid;
use OpenSkos2\SkosXl\LabelManager;
use OpenSkos2\SkosXl\Label;

class Concept extends Resource
{

    const TYPE = 'http://www.w3.org/2004/02/skos/core#Concept';

    /**
     * All possible statuses
     */
    const STATUS_CANDIDATE = 'candidate';
    const STATUS_APPROVED = 'approved';
    const STATUS_REDIRECTED = 'redirected';
    const STATUS_NOT_COMPLIANT = 'not_compliant';
    const STATUS_REJECTED = 'rejected';
    const STATUS_OBSOLETE = 'obsolete';
    const STATUS_DELETED = 'deleted';
    const STATUS_EXPIRED = 'expired';

    
    /**
     * Get list of all available concept statuses.
     * @return array
     */
    public static function getAvailableStatuses()
    {
        return [
            self::STATUS_CANDIDATE,
            self::STATUS_APPROVED,
            self::STATUS_REDIRECTED,
            self::STATUS_NOT_COMPLIANT,
            self::STATUS_REJECTED,
            self::STATUS_OBSOLETE,
            self::STATUS_DELETED,
            self::STATUS_EXPIRED,
        ];
    }

    public static $labelsMap = [
        SkosXl::PREFLABEL => Skos::PREFLABEL,
        SkosXl::ALTLABEL => Skos::ALTLABEL,
        SkosXl::HIDDENLABEL => Skos::HIDDENLABEL,
    ];
    

    /**
     * Resource constructor.
     * @param string $uri , optional
     */
    public function __construct($uri = null)
    {
        parent::__construct($uri);
        $this->addProperty(Rdf::TYPE, new Uri(self::TYPE));
    }

      /**
     * Check if the resource is deleted
     * @TODO Separate in StatusAwareResource class or something like that
     * @return boolean
     */
    public function isDeleted()
    {
        if ($this->getStatus() === self::STATUS_DELETED) {
            return true;
        }
        return false;
    }

    public function getSkosCollection()
    {
        if (!$this->hasProperty(OpenSkos::INSKOSCOLLECTION)) {
            return null;
        } else {
            return $this->getProperty(OpenSkos::INSKOSCOLLECTION)[0]->getValue();
        }
    }

    /**
     * Gets preview title for the concept.
     * @param string $language
     * @return string
     * @throws Exception
     */
    public function getCaption($language = null)
    {
        if ($this->hasPropertyInLanguage(Skos::PREFLABEL, $language)) {
            return $this->getPropertyFlatValue(Skos::PREFLABEL, $language);
        } else {
            return $this->getPropertyFlatValue(Skos::PREFLABEL);
        }
    }

    /**
     * Checks if the concept is top concept for the specified scheme.
     * @param string $conceptSchemeUri
     * @return bool
     */
    public function isTopConceptOf($conceptSchemeUri)
    {
        if (!$this->isPropertyEmpty(Skos::TOPCONCEPTOF)) {
            return in_array($conceptSchemeUri, $this->getProperty(Skos::TOPCONCEPTOF));
        } else {
            return false;
        }
    }

    /**
     * Does the concept have any relations or mapping properties.
     * @return bool
     */
    public function hasAnyRelations()
    {
        $relationProperties = array_merge(
            Resource::$classes['SemanticRelations'], Resource::$classes['MappingProperties']
        );
        foreach ($relationProperties as $relationProperty) {
            if (!$this->isPropertyEmpty($relationProperty)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Does the concept has any xl labels in it.
     * @return boolean
     */
    public function hasXlLabels()
    {
        foreach (self::$classes['SkosXlLabels'] as $predicate) {
            if (!$this->isPropertyEmpty($predicate)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Does the concept has any xl labels in it.
     * @return boolean
     */
    public function hasSimpleLabels()
    {
        foreach (self::$classes['LexicalLabels'] as $predicate) {
            if (!$this->isPropertyEmpty($predicate)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Ensures the concept has metadata for tenant, set, creator, date submited, modified and other like this.
     * @param \OpenSkos2\Tenant $tenant
     * @param \OpenSkos2\Set $set
     * @param \OpenSkos2\Person $person
     * @param \OpenSkos2\PersonManager $personManager
     * @param \OpenSkos2\LabelManager $labelManager, 
     * @param  Resource $existingConcept, optional $existingResource of one of concrete child types used for update 
     * $oldStatus will be derived from $existingResource
     */
    public function ensureMetadata(
        \OpenSkos2\Tenant $tenant, 
        \OpenSkos2\Set $set = null, 
        \OpenSkos2\Person $person = null, 
        PersonManager $personManager = null,
        LabelManager $labelManager=null, 
        $existingConcept = null,
        $forceCreationOfXl = false)
    {

        $nowLiteral = function () {
            return new Literal(date('c'), null, Literal::TYPE_DATETIME);
        };

        $forFirstTimeInOpenSkos = [
            OpenSkos::UUID => new Literal(Uuid::uuid4()),
            DcTerms::PUBLISHER => new Uri($tenant->getUri()),
            OpenSkos::TENANT => $tenant->getCode(),
            // @TODO Make status dependent on if the tenant has statuses system enabled.
            OpenSkos::STATUS => new Literal(Concept::STATUS_CANDIDATE),
            DcTerms::DATESUBMITTED => $nowLiteral()
        ];

        if (!empty($set)) {
            // @TODO Aways make sure we have a set defined. Maybe a default set for the tenant.
            $forFirstTimeInOpenSkos[OpenSkos::SET] = new Uri($set->getUri());
        }

        foreach ($forFirstTimeInOpenSkos as $property => $defaultValue) {
            if (!$this->hasProperty($property)) {
                $this->setProperty($property, $defaultValue);
            }
        }

        $this->resolveCreator($person, $personManager);

        $this->setModified($person);

        $this->handleStatusChange($person, $existingConcept);

        // Create all asserted labels
        $labelHelper = new Concept\LabelHelper($labelManager);
        $labelHelper->assertLabels($this, $forceCreationOfXl);
    }

    /**
     * Handle change in status.
     * @param Person $person
     * @param existingConcept
     */
    public function handleStatusChange($person, $existingConcept = null)
    {
        if ($existingConcept == null) {
            $oldStatus=null;
        }
        
        $nowLiteral = function () {
            return new Literal(date('c'), null, \OpenSkos2\Rdf\Literal::TYPE_DATETIME);
        };

        $personUri = new Uri($person->getUri());

        // Status is updated
        if ($oldStatus != $this->getStatus()) {
            $this->unsetProperty(DcTerms::DATEACCEPTED);
            $this->unsetProperty(OpenSkos::ACCEPTEDBY);
            $this->unsetProperty(OpenSkos::DATE_DELETED);
            $this->unsetProperty(OpenSkos::DELETEDBY);
            switch ($this->getStatus()) {
                case \OpenSkos2\Concept::STATUS_APPROVED:
                    $this->addProperty(DcTerms::DATEACCEPTED, $nowLiteral());
                    $this->addProperty(OpenSkos::ACCEPTEDBY, $personUri);
                    break;
                case \OpenSkos2\Concept::STATUS_DELETED:
                    $this->addProperty(OpenSkos::DATE_DELETED, $nowLiteral());
                    $this->addProperty(OpenSkos::DELETEDBY, $personUri);
                    break;
            }
        }
    }
    

    
    /**
     * Generates an uri for the concept.
     * Requires a URI from to an openskos set
     * @return string
     */
    public function selfGenerateUri(\OpenSkos2\Tenant $tenant, \OpenSkos2\Set $set, $conceptManager)
    {
        $init = $conceptManager->getInitArray();
        if (!$init["custom.default_urigenerate"]) {
            $customGen = new UriGeneration();
            return $customGen->generateUri($conceptManager, $this);
        }
        
        $identifierHelper = new Concept\IdentifierHelper($tenant, $conceptManager);

        $uri = $identifierHelper->generateUri($this);

        return $uri;
    }

    /**
     * Loads the XL labels and replaces the default URI value with the full resource
     * @param LabelManager $labelManager
     */
    public function loadFullXlLabels($labelManager)
    {
        foreach (Concept::$classes['SkosXlLabels'] as $xlLabelPredicate) {
            $fullXlLabels = [];
            foreach ($this->getProperty($xlLabelPredicate) as $xlLabel) {
                if ($xlLabel instanceof Label) {
                    $fullXlLabels[] = $xlLabel;
                } else {
                    $fullXlLabels[] = $labelManager->fetchByUri($xlLabel);
                }
            }
            $this->setProperties($xlLabelPredicate, $fullXlLabels);
        }
    }

   
}
