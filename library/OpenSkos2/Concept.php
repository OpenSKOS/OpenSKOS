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
use OpenSkos2\ConceptManager;
use OpenSkos2\Exception\UriGenerationException;
use OpenSkos2\Exception\OpenSkosException;
use OpenSkos2\Exception\ResourceNotFoundException;
use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Namespaces\Rdf;
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Namespaces\Foaf;
use OpenSkos2\Rdf\Literal;
use OpenSkos2\Rdf\Resource;
use OpenSkos2\Rdf\ResourceManager;
use OpenSkos2\Rdf\Uri;
use OpenSkos2\Tenant;
use OpenSkos2\Custom\UriGeneration;
use OpenSkos2\PersonManager;
use Rhumsaa\Uuid\Uuid;

class Concept extends Resource
{

    const TYPE = 'http://www.w3.org/2004/02/skos/core#Concept';

    /**
     * Resource constructor.
     * @param string $uri , optional
     */
    public function __construct($uri = null)
    {
        parent::__construct($uri);
        $this->addProperty(Rdf::TYPE, new Uri(self::TYPE));
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
            Resource::$classes['SemanticRelations'],
            Resource::$classes['MappingProperties']
        );
        foreach ($relationProperties as $relationProperty) {
            if (!$this->isPropertyEmpty($relationProperty)) {
                return true;
            }
        }
        return false;
    }

    
    /**
     * Ensures the concept has metadata for tenant, set, creator, date submited, modified and other like this.
     * @param Tenant $tenant
     * @param Set $set
     * @param Person $person
     * @param PersonManager $personManager
     * @param string , optional $oldStatus
     */
    public function ensureMetadata(
        Tenant $tenant, 
        Set $set, 
        Person $person, 
        PersonManager $personManager, 
        $oldStatus = null)

    {
        $nowLiteral = function () {
            return new Literal(date('c'), null, Literal::TYPE_DATETIME);
        };
/* tmp help, meertens code

        if ($existingConcept === null) { // a completely new concept under creation
            $this->setProperty(DcTerms::CREATOR, new Uri($userUri));
            $this->setProperty(DcTerms::DATESUBMITTED, $nowLiteral());
*/
        
        $forFirstTimeInOpenSkos = [
            OpenSkos::UUID => new Literal(Uuid::uuid4()),
            OpenSkos::TENANT => new Uri($tenant->getUri()),
            // @TODO Make status dependent on if the tenant has statuses system enabled.
            OpenSkos::STATUS => new Literal(Concept::STATUS_CANDIDATE),
            DcTerms::DATESUBMITTED => $nowLiteral(),
        ];
        
        if (!empty($set)) {
            if (!($set instanceof Uri)) {
                throw new OpenSkosException('The set must be instance of Uri');
            }
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
        
        $this->handleStatusChange($person, $oldStatus);
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
/* tmp Meertens code
            $this->unsetProperty(OpenSkos::STATUS);
            $this->addProperty(OpenSkos::STATUS, new Literal(Resource::STATUS_CANDIDATE));
        } else {
            $this->setProperty(DcTerms::MODIFIED, $nowLiteral());
            $this->addProperty(DcTerms::CONTRIBUTOR, new Uri($userUri));

            if ($existingConcept->getStatus() !== $this->getStatus()) {
                $this->unsetProperty(DcTerms::DATEACCEPTED);
                $this->unsetProperty(OpenSkos::ACCEPTEDBY);
                $this->unsetProperty(OpenSkos::DATE_DELETED);
                $this->unsetProperty(OpenSkos::DELETEDBY);

                switch ($this->getStatus()) {
                    case Resource::STATUS_APPROVED:
                        $this->addProperty(DcTerms::DATEACCEPTED, $nowLiteral());
                        $this->addProperty(OpenSkos::ACCEPTEDBY, new Uri($userUri));
                        break;
                    case Resource::STATUS_DELETED:
                        $this->addProperty(OpenSkos::DATE_DELETED, $nowLiteral());
                        $this->addProperty(OpenSkos::DELETEDBY, new Uri($userUri));
                        break;
                }
            }

            $this->unsetProperty(OpenSkos::UUID);
            $this->setProperty(OpenSkos::UUID, $existingConcept->getUuid());

            $creators = $existingConcept->getProperty(DcTerms::CREATOR);
            if (count($creators) < 1) {
                $this->setProperty(DcTerms::CREATOR, new Literal(Roles::UNKNOWN));
            } else {
                $this->setProperty(DcTerms::CREATOR, $creators[0]);
            }
            $dateSubmitted = $existingConcept->getProperty(DcTerms::DATESUBMITTED);
            if (count($dateSubmitted) > 0) {
                $this->setProperty(
                    DcTerms::DATESUBMITTED,
                    new Literal(
                        $dateSubmitted[0],
                        null,
                        Literal::TYPE_DATETIME
                    )
                );
*/
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
     * Generate notation unique per tenant. Based on tenant notations sequence.
     * @return string
     */
    public function selfGenerateNotation(Tenant $tenant, ConceptManager $conceptManager)
    {
        // @TODO Move that and uri generate to separate class.
        // @TODO A raise condition is possible. The validation will fail in that case -
        // so should not be problem.
        // Meertens:
        // this is changed because $maxNumericNotation returns now zero if there
        // is no records in the solr (otherwise there was a crash in solr/resourceManager
        // on empty database with the attempt to ask for current on the empty iterator
        $maxNumericNotation = $conceptManager->fetchMaxNumericNotationFromIndex($tenant);
        $notation = $maxNumericNotation + 1;
        $this->addProperty(
            Skos::NOTATION,
            new Literal($notation)
        );
    }

    public function selfGenerateUri(ResourceManager $manager, $tenant, $set)
    {
        $init = $manager->getInitArray();
        if (!$init["custom.default_urigenerate"]) {
            $customGen = new UriGeneration();
            return $customGen->selfGenerateUri($manager, $this);
        }

        $uuid = Uuid::uuid4();
       
        if (!$this->isBlankNode()) {
            throw new UriGenerationException(
                'The concept already has an uri. Can not generate new one.'
            );
        }

        if ($this->isPropertyEmpty(Skos::NOTATION) && $tenant->isNotationAutoGenerated()) {
            $this->selfGenerateNotation($tenant, $manager);
        }

        if ($this->isPropertyEmpty(Skos::NOTATION)) {
            $uri = $this->assembleUri($tenant, $set, $uuid, $init);
        } else {
            $uri = $this->assembleUri(
                $tenant,
                $set,
                $uuid,
                $this->getProperty(Skos::NOTATION)[0]->getValue(),
                $init
            );
        }

        if ($manager->askForUri($uri, true)) {
            throw new UriGenerationException(
                'The generated uri "' . $uri . '" is already in use.'
            );
        }

        $this->setUri($uri);
        $this->setProperty(OpenSkos::UUID, new Literal($uuid));
        return $uri;
    }

    /**
     * Generates concept uri from collection and notation
     * @param string $setUri
     * @param string $firstNotation, optional. New uuid will be used if empty
     * @return string
     */
    protected function assembleUri($tenant, $set, $uuid, $firstNotation, $init)
    {
        $conceptBaseUris = $set->getProperty(OpenSkos::CONCEPTBASEURI);
        if (count($conceptBaseUris) < 1) {
            throw new UriGenerationException(
                'No concept base uri is given in the set description '
                . '(you may want to use epic service whch does not require thsi uri)'
            );
        } else {
            if ($conceptBaseUris[0] instanceof Uri) {
                $conceptBaseUri = $conceptBaseUris[0]->getUri();
            } else {
                $conceptBaseUri = $conceptBaseUris[0]->getValue();
            }
        }

        
        $separator = '/';

        $baseUri = rtrim($conceptBaseUri, $separator);

        if (empty($firstNotation)) {
            $uri = $baseUri . $separator . $uuid;
        } else {
            $uri = $baseUri . $separator . $firstNotation;
        }

        return $uri;
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
    protected function resolveCreator(Person $person, PersonManager $personManager)
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
