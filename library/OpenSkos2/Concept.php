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
use OpenSkos2\Namespaces\Dc;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Namespaces\Rdf;
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Namespaces\Foaf;
use OpenSkos2\Rdf\Literal;
use OpenSkos2\Rdf\Resource;
use OpenSkos2\Rdf\Uri;
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
     * Ensures the concept has metadata for tenant, set, creator, date submited, modified and other like this.
     * @param $tenantUri
     * @param setUri
     * @param \OpenSkos2\Person $person
     * @param \OpenSkos2\PersonManager $personManager
     * @param  Resource $existingConcept, optional $existingResource of one of concrete child types used for update 
     * $oldStatus will be derived from $existingResource
     */
    public function ensureMetadata(
    $tenantUri, $setUri, \OpenSkos2\Person $person, \OpenSkos2\PersonManager $personManager, $existingConcept=null)
    {
        $nowLiteral = function () {
            return new Literal(date('c'), null, Literal::TYPE_DATETIME);
        };
        if ($existingConcept == null) { // a completely new concept under creation
            $forFirstTimeInOpenSkos = [
                // OpenSkos::UUID => new Literal(Uuid::uuid4()), uuid generation is a part of uri generation
                // 
                // OpenSkos::TENANT => new Uri($tenant->getUri()), 
                // tenant is not a part of the concept rdf because it is derived from the concept's schema's concept schema via set
                // @TODO Make status dependent on if the tenant has statuses system enabled.
                OpenSkos::STATUS => new Literal(Concept::STATUS_CANDIDATE),
                DcTerms::DATESUBMITTED => $nowLiteral(),
                DcTerms::CREATOR => new Uri($person->getUri())
            ];

            // set is not a part of the concept rdf because it is derived from a concept's schema
            /*
              if (!empty($setUri)) {
              if (!($set instanceof Uri)) {
              throw new OpenSkosException('The set must be instance of Uri');
              }
              // @TODO Aways make sure we have a set defined. Maybe a default set for the tenant.
              $forFirstTimeInOpenSkos[OpenSkos::SET] = new Uri($set->getUri());
              }
             * */

            foreach ($forFirstTimeInOpenSkos as $property => $defaultValue) {
                if (!$this->hasProperty($property)) {
                    $this->setProperty($property, $defaultValue);
                }
            }
        } else { 
            $creatorUri = $existingConcept->getProperty(DcTerms::CREATOR);
            if (count($creatorUri)>0) {
                $this->setProperty(DcTerms::CREATOR, $creatorUri[0]);
            }
            $dateSubmitted = $existingConcept->getProperty(DcTerms::DATESUBMITTED);
            if (count($dateSubmitted)>0) {
                $this->setProperty(DcTerms::DATESUBMITTED, $dateSubmitted[0]);
            }
            $oldStatus = $existingConcept->getStatus();
            $this->handleStatusChange($person, $oldStatus);
            $this->setModified($person);
        }
        $this->resolveCreator($person, $personManager);
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
     * @param Uri|\OpenSkos2\Person $person
     * @param string $oldStatus
     */
    public function handleStatusChange(\OpenSkos2\Person $person, $oldStatus = null)
    {
        $nowLiteral = function () {
            return new Literal(date('c'), null, \OpenSkos2\Rdf\Literal::TYPE_DATETIME);
        };
        // Status is updated
        if ($oldStatus != $this->getStatus()) {
            $this->unsetProperty(DcTerms::DATEACCEPTED);
            $this->unsetProperty(OpenSkos::ACCEPTEDBY);
            $this->unsetProperty(OpenSkos::DATE_DELETED);
            $this->unsetProperty(OpenSkos::DELETEDBY);

            switch ($this->getStatus()) {
                case \OpenSkos2\Concept::STATUS_APPROVED:
                    $this->addProperty(DcTerms::DATEACCEPTED, $nowLiteral());
                    $this->addProperty(OpenSkos::ACCEPTEDBY, new Uri($person->getUri()));
                    break;
                case \OpenSkos2\Concept::STATUS_DELETED:
                    $this->addProperty(OpenSkos::DATE_DELETED, $nowLiteral());
                    $this->addProperty(OpenSkos::DELETEDBY, new Uri($person->getUri()));
                    break;
            }
        }
    }

    /**
     * Generate notation unique per tenant. Based on tenant notations sequence.
     * @return string
     */
    public function selfGenerateNotation(\OpenSkos2\Tenant $tenant, \OpenSkos2\ConceptManager $conceptManager)
    {
        // @TODO Move that and uri generate to separate class.
        // @TODO A raise condition is possible. The validation will fail in that case -
        // so should not be problem.
        // Meertens:
        // this $maxNumericNotation returns now zero if there is no records in the solr (otherwise there was a crash in solr/resourceManager on empty database with the attempt to ask for current on the empty iterator
        $maxNumericNotation = $conceptManager->fetchMaxNumericNotationFromIndex($tenant);
        $notation = $maxNumericNotation + 1;
        $this->addProperty(
            Skos::NOTATION, new Literal($notation)
        );
    }

    public function selfGenerateUri($tenant, $set, ConceptManager $manager)
    {
        $init = $manager->getInitArray();
        if (!$init["custom.default_urigenerate"]) {
            $customGen = new UriGeneration();
            return $customGen->generateUri($manager, $this);
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
                $tenant, $set, $uuid, $this->getProperty(Skos::NOTATION)[0]->getValue(), $init
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
    protected function resolveCreator(\OpenSkos2\Person $person, \OpenSkos2\PersonManager $personManager)
    {
        $dcCreator = $this->getProperty(Dc::CREATOR);
        $dcTermsCreator = $this->getProperty(DcTerms::CREATOR);

        // Set the creator to the apikey user
        if (empty($dcCreator) && empty($dcTermsCreator)) {
            $this->setCreator(null, new Uri($person->getUri()));
            return;
        }

        // Check if the dc:Creator is Uri or Literal
        if (!empty($dcCreator) && empty($dcTermsCreator)) {
            $dcCreator = $dcCreator[0];

            if ($dcCreator instanceof Literal) {
                $creatorPerson = $personManager->fetchByName($dcCreator->getValue());
                $dcTermsCreator = new Uri($creatorPerson->getUri());
            } elseif ($dcCreator instanceof Uri) {
                $dcTermsCreator = new Uri($person->getUri());
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
                $dcTermsCreator = new Uri($personManager->fetchByName($dcTermsCreator->getValue()));
            } elseif ($dcTermsCreator instanceof Uri) {
                // We are ok with this use case even if the Uri is not present in our system ??             
               $dcTermsCreator = new Uri($dcTermsCreator->getUri()); 
            } else {
                throw new OpenSkosException('dcTerms:Creator is not Literal nor Uri. Something is very wrong.');
            }

            //$this->setCreator($dcCreator, $dcTermsCreator);
            $this->setCreator(null, $dcTermsCreator); // dc-creator as literal will be fetched as an additional propery (together with set and tenant) for get and index requests for concepts, otherwise.
            return;
        }

        // Resolve conflicting dc:Creator and dcTerms:Creator values
        if (!empty($dcCreator) && !empty($dcTermsCreator)) {
            $dcCreator = $dcCreator[0];
            $dcTermsCreator = $dcTermsCreator[0];
            try {
                $dcTermsCreatorName = $personManager->fetchByUri($dcTermsCreator->getUri())->getProperty(Foaf::NAME);
            } catch (ResourceNotFoundException $err) {
                // We cannot find the resource so just leave values as they are ??
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
