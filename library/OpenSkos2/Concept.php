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

use OpenSkos2\Exception\UriGenerationException;
use OpenSkos2\Exception\OpenSkosException;
use OpenSkos2\Exception\ResourceNotFoundException;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Namespaces\Dc;
use OpenSkos2\Namespaces\Rdf;
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Namespaces\SkosXl;
use OpenSkos2\Namespaces\Foaf;
use OpenSkos2\Rdf\Literal;
use OpenSkos2\Rdf\Resource;
use OpenSkos2\Rdf\Uri;
use OpenSkos2\Tenant;
use OpenSkos2\Person;
use OpenSkos2\ConceptManager;
use OpenSkos2\PersonManager;
use OpenSKOS_Db_Table_Row_Tenant;
use OpenSKOS_Db_Table_Tenants;
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
        ];
    }
    
    public static $classes = array(
        'ConceptSchemes' => [
            Skos::CONCEPTSCHEME,
            Skos::INSCHEME,
            Skos::HASTOPCONCEPT,
            Skos::TOPCONCEPTOF,
        ],
        'LexicalLabels' => [
            Skos::PREFLABEL,
            Skos::ALTLABEL,
            Skos::HIDDENLABEL,
        ],
        'SkosXlLabels' => [
            SkosXl::PREFLABEL,
            SkosXl::ALTLABEL,
            SkosXl::HIDDENLABEL,
        ],
        'Notations' => [
            Skos::NOTATION,
        ],
        'DocumentationProperties' => [
            Skos::CHANGENOTE,
            Skos::DEFINITION,
            Skos::EDITORIALNOTE,
            Skos::EXAMPLE,
            Skos::HISTORYNOTE,
            Skos::NOTE,
            Skos::SCOPENOTE,
        ],
        'SemanticRelations' => [
            Skos::BROADER,
            Skos::BROADERTRANSITIVE,
            Skos::NARROWER,
            Skos::NARROWERTRANSITIVE,
            Skos::RELATED,
            Skos::SEMANTICRELATION,
        ],
        'ConceptCollections' => [
            Skos::COLLECTION,
            Skos::ORDEREDCOLLECTION,
            Skos::MEMBER,
            Skos::MEMBERLIST,
        ],
        'MappingProperties' => [
            Skos::BROADMATCH,
            Skos::CLOSEMATCH,
            Skos::EXACTMATCH,
            Skos::MAPPINGRELATION,
            Skos::NARROWMATCH,
            Skos::RELATEDMATCH,
        ],
    );
    
    public static $labelsMap = [
        SkosXl::PREFLABEL => Skos::PREFLABEL,
        /*
        SkosXl::ALTLABEL => Skos::ALTLABEL,
        SkosXl::HIDDENLABEL => Skos::HIDDENLABEL,
        */
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
     * Gets preview title for the concept.
     * @param string $language
     * @return string
     * @throws \Exception
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
     * Get openskos:uuid if it exists
     * Identifier for backwards compatability. Always use uri as identifier.
     * @return string|null
     */
    public function getUuid()
    {
        if ($this->hasProperty(OpenSkos::UUID)) {
            return (string)$this->getPropertySingleValue(OpenSkos::UUID);
        } else {
            return null;
        }
    }

    /**
     * Get tenant
     *
     * @return Literal
     */
    public function getTenant()
    {
        $values = $this->getProperty(OpenSkos::TENANT);
        if (isset($values[0])) {
            return $values[0];
        }
    }
    
    /**
     * Get institution row
     * @TODO Remove dependency on OpenSKOS v1 library
     * @return OpenSKOS_Db_Table_Row_Tenant
     */
    public function getInstitution()
    {
        // @TODO Remove dependency on OpenSKOS v1 library
        $model = new OpenSKOS_Db_Table_Tenants();
        return $model->find($this->getTenant())->current();
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
            self::$classes['SemanticRelations'],
            self::$classes['MappingProperties']
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
     * @param string $tenantCode
     * @param Uri $set
     * @param Uri $person
     * @param LabelManager $labelManager
     * @param string , optional $oldStatus
     */
    public function ensureMetadata(
        $tenantCode,
        $set,
        Uri $person,
        LabelManager $labelManager,
        PersonManager $personManager,
        $oldStatus = null,
        $forceCreationOfXl = false
    ) {
        $nowLiteral = function () {
            return new Literal(date('c'), null, \OpenSkos2\Rdf\Literal::TYPE_DATETIME);
        };
        
        $personUri = new Uri($person->getUri());
        
        $forFirstTimeInOpenSkos = [
            OpenSkos::UUID => new Literal(Uuid::uuid4()),
            OpenSkos::TENANT => new Literal($tenantCode),
            // @TODO Make status dependent on if the tenant has statuses system enabled.
            OpenSkos::STATUS => new Literal(Concept::STATUS_CANDIDATE),
            DcTerms::DATESUBMITTED => $nowLiteral(),
        ];
        
        if (!empty($set)) {
            if (!($set instanceof Uri)) {
                throw new OpenSkosException('The set must be instance of Uri');
            }
            // @TODO Aways make sure we have a set defined. Maybe a default set for the tenant.
            $forFirstTimeInOpenSkos[OpenSkos::SET] = $set;
        }
        
        foreach ($forFirstTimeInOpenSkos as $property => $defaultValue) {
            if (!$this->hasProperty($property)) {
                $this->setProperty($property, $defaultValue);
            }
        }
        
        $this->resolveCreator($person, $personManager);
        
        $this->setModified($person);
        
        $this->handleStatusChange($person, $oldStatus);
        
        // Create all asserted labels
        $labelHelper = new Concept\LabelHelper($labelManager);
        $labelHelper->assertLabels($this, $forceCreationOfXl);
    }
    
    /**
     * Mark the concept as modified.
     * @param Uri|Person $person
     */
    public function setModified($person)
    {
        $nowLiteral = function () {
            return new Literal(date('c'), null, \OpenSkos2\Rdf\Literal::TYPE_DATETIME);
        };
        
        $personUri = new Uri($person->getUri());
        
        $this->setProperty(DcTerms::MODIFIED, $nowLiteral());
        $this->setProperty(OpenSkos::MODIFIEDBY, $personUri);
    }
    
    /**
     * Handle change in status.
     * @param Uri|Person $person
     * @param string $oldStatus
     */
    public function handleStatusChange($person, $oldStatus = null)
    {
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
     * Requires a URI from to an openskos collection
     * @return string
     */
    public function selfGenerateUri(Tenant $tenant, ConceptManager $conceptManager)
    {
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
    
    /**
     * Resolve the creator in all use cases:
     * - dc:creator is set but dcterms:creator is not
     * - dcterms:creator is set as Uri
     * - dcterms:creator is set as literal value
     * - no creator is set
     * @param Person $person
     * @param PersonManager $personManager
     */
    public function resolveCreator(Person $person, PersonManager $personManager)
    {
        $dcCreator = $this->getProperty(Dc::CREATOR);
        $dcTermsCreator = $this->getProperty(DcTerms::CREATOR);
        
        // Set the creator to the apikey user
        if (empty($dcCreator) && empty($dcTermsCreator)) {
            $this->setCreator(null, $person);
            return;
        }
        
        // Check if the dc:Creator is Uri or Literal
        if (!empty($dcCreator) && empty($dcTermsCreator)) {
            $dcCreator = $dcCreator[0];
            
            if ($dcCreator instanceof Literal) {
                $dcTermsCreator = $personManager->fetchByName($dcCreator->getValue());
            } elseif ($dcCreator instanceof Uri) {
                $dcTermsCreator = $dcCreator;
                $dcCreator = null;
            } else {
                throw Exception('dc:Creator is not Literal nor Uri. Something is very wrong.');
            }
            
            $this->setCreator($dcCreator, $dcTermsCreator);
            return;
        }

        // Check if the dcTerms:Creator is Uri or Literal
        if (empty($dcCreator) && !empty($dcTermsCreator)) {
            $dcTermsCreator = $dcTermsCreator[0];
            
            if ($dcTermsCreator instanceof Literal) {
                $dcCreator = $dcTermsCreator;
                $dcTermsCreator = $personManager->fetchByName($dcTermsCreator->getValue());
            } elseif ($dcTermsCreator instanceof Uri) {
                // We are ok with this use case even if the Uri is not present in our system
            } else {
                throw new OpenSkosException('dcTerms:Creator is not Literal nor Uri. Something is very wrong.');
            }
            
            $this->setCreator($dcCreator, $dcTermsCreator);
            return;
        }
        
        // Resolve conflicting dc:Creator and dcTerms:Creator values
        if (!empty($dcCreator) && !empty($dcTermsCreator)) {
            $dcCreator = $dcCreator[0];
            $dcTermsCreator = $dcTermsCreator[0];
            try {
                $dcTermsCreatorName = $personManager->fetchByUri($dcTermsCreator->getUri())->getProperty(Foaf::NAME);
            } catch (ResourceNotFoundException $err) {
                // We cannot find the resource so just leave values as they are
                $dcTermsCreatorName = null;
            }
            
            if (!empty($dcTermsCreatorName) && $dcTermsCreatorName[0]->getValue() !== $dcCreator->getValue()) {
                throw new OpenSkosException('dc:Creator and dcTerms:Creator names do not match.');
            }
            
            $this->setCreator($dcCreator, $dcTermsCreator);
            return;
        }
    }
    
    protected function setCreator($dcCreator, $dcTermsCreator)
    {
        if (!empty($dcCreator)) {
            $this->setProperty(Dc::CREATOR, $dcCreator);
        }
        
        if (!empty($dcTermsCreator)) {
            $this->setProperty(DcTerms::CREATOR, $dcTermsCreator);
        }
    }
}
