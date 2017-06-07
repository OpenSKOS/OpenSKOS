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

namespace OpenSkos2\Rdf;

use DateTime;
use OpenSkos2\Roles;
use OpenSkos2\Exception\OpenSkosException;
use OpenSkos2\Exception\UriGenerationException;
use OpenSkos2\Namespaces as Namespaces;
use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Namespaces\Rdf;
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Rdf\Literal;
use OpenSkos2\Rdf\Object as RdfObject;
use OpenSkos2\Rdf\Uri;
use OpenSkos2\RelationType;
use Rhumsaa\Uuid\Uuid;
use OpenSkos2\Custom\UriGeneration;

// Meertens: Picturae changes starting from 22/11/2016 are taken

class Resource extends Uri implements ResourceIdentifier
{
    /**
     * @TODO Separate in StatusAwareResource class or something like that
     * openskos:status value which marks a resource as deleted.
     */

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

    protected $properties = [];

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

    public static $classes = array(
        'ConceptSchemes' => [
            Skos::CONCEPTSCHEME,
            Skos::INSCHEME,
            Skos::HASTOPCONCEPT,
            Skos::TOPCONCEPTOF,
        ],
        'LexicalLabels' => [
            Skos::ALTLABEL,
            Skos::HIDDENLABEL,
            Skos::PREFLABEL,
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
        'SkosCollections' => [
            OpenSkos::INSKOSCOLLECTION,
            Skos::ORDEREDCOLLECTION, // ??
            Skos::MEMBER, //??
            Skos::MEMBERLIST, //?
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

    public static function getLanguagedProperties()
    {
        $retVal = array_merge(self::$classes['DocumentationProperties'], [DcTerms::DESCRIPTION, DcTerms::TITLE]);
        return $retVal;
    }

    /**
     * @return array of RdfObject[]
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @param string $predicate
     * @return RdfObject[]
     */
    public function getProperty($predicate)
    {
        if (!isset($this->properties[$predicate])) {
            return [];
        } else {
            return $this->properties[$predicate];
        }
    }

    // used in Tenant and Set
    public function getCode()
    {
        return $this->getPropertyOneLiteralValue(OpenSkos::CODE);
    }

    public function getTitle()
    {
        return $this->getPropertyOneLiteralValue(DcTerms::TITLE);
    }

    private function getPropertyOneLiteralValue($propertyURI)
    {
        $values = $this->getProperty($propertyURI);
        if (isset($values[0])) {
            return $values[0];
        } else {
            return new Literal(Roles::UNKNOWN);
        }
    }

    /**
     * @param string $predicate
     * @param RdfObject $value
     * @return $this
     */
    public function addProperty($predicate, RdfObject $value)
    {
        $this->handleSpecialProperties($predicate, $value);
        $this->properties[$predicate][] = $value;
        return $this;
    }

    /**
     * Add multiple values at once, keeps the existing values
     * @param string $predicate
     * @param RdfObject[] $values
     * @return $this
     */
    public function addProperties($predicate, array $values)
    {
        foreach ($values as $value) {
            $this->addProperty($predicate, $value);
        }
        return $this;
    }

    /**
     * Make sure the property is only added once
     *
     * @param string $predicate
     * @param RdfObject $value
     * @return Resource
     */
    public function addUniqueProperty($predicate, RdfObject $value)
    {
        if (!isset($this->properties[$predicate])) {
            $this->addProperty($predicate, $value);
            return $this;
        }
        foreach ($this->properties[$predicate] as $obj) {
            if ($obj instanceof Literal && $value instanceof Literal) {
                if ($obj->getValue() === $value->getValue() && $obj->getLanguage() === $value->getLanguage()) {
                    return $this;
                }
            } elseif ($obj instanceof Uri && $value instanceof Uri) {
                if ($obj->getUri() === $value->getUri()) {
                    return $this;
                }
            }
        }
        $this->addProperty($predicate, $value);
        return $this;
    }

    /**
     * Set property, overwrite existing values.
     * @param string $predicate
     * @param RdfObject $value
     * @return $this
     */
    public function setProperty($predicate, RdfObject $value)
    {
        $this->unsetProperty($predicate)
            ->addProperty($predicate, $value);
        return $this;
    }

    /**
     * Set multiple values at once, override existing values
     * @param string $predicate
     * @param RdfObject[] $values
     * @return $this
     */
    public function setProperties($predicate, array $values)
    {
        $this->unsetProperty($predicate)
            ->addProperties($predicate, $values);
        return $this;
    }

    /**
     * @param string $predicate
     * @return $this
     */
    public function unsetProperty($predicate)
    {
        unset($this->properties[$predicate]);
        return $this;
    }

    /**
     * @param string $predicate
     * @return bool
     */
    public function hasProperty($predicate)
    {
        return isset($this->properties[$predicate]);
    }

    /**
     * @param string $predicate
     * @return bool
     */
    public function isPropertyEmpty($predicate)
    {
        return !isset($this->properties[$predicate]) || $this->properties[$predicate] === null ||
            $this->properties[$predicate] === '';
    }

    /**
     * @param string $predicate
     * @return bool
     */
    public function isPropertyTrue($predicate)
    {
        if (!$this->isPropertyEmpty($predicate)) {
            $values = $this->getProperty($predicate);
            return (bool) $values[0]->getValue();
        }
        return false;
    }

    /**
     * @TODO Separate in StatusAwareResource class or something like that
     * @return string|null
     */
    public function getStatus()
    {
        if (!$this->hasProperty(OpenSkos::STATUS)) {
            return null;
        } else {
            return $this->getProperty(OpenSkos::STATUS)[0]->getValue();
        }
    }

    /**
     * Check if the resource is deleted
     * @TODO Separate in StatusAwareResource class or something like that
     * @return boolean
     */
    public function isDeleted()
    {
        if ($this->getStatus() === Resource::STATUS_DELETED) {
            return true;
        }
        return false;
    }

    /**
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @param string $uri
     */
    public function setUri($uri)
    {
        $this->uri = $uri;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return current($this->getProperty(Rdf::TYPE));
    }

    public function getCreator()
    {
        if ($this->hasProperty(DcTerms::CREATOR)) {
            $tmp = current($this->getProperty(DcTerms::CREATOR));
            if ($tmp instanceof Literal) { // literal value for UNKNOWN only
                return $tmp->getValue();
            } else {
                if ($tmp instanceof Uri) {
                    return $tmp->getUri();
                } else {
                    return null;
                }
            }
        } else {
            return null;
        }
    }

    public function getDateSubmitted()
    {
        return current($this->getProperty(DcTerms::DATESUBMITTED));
    }

    public function getUuid()
    {
        return $this->getPropertyOneLiteralValue(OpenSkos::UUID);
    }

// TODO : ask Picturae about this function, look for usages before
    /**
     * @return string
     */
    public function getCaption($language = null)
    {
        return $this->uri;
    }

    /**
     * Is the current resource a blank node.
     * It is if no uri given or generated uri starting with _:
     * @return boolean
     */
    public function isBlankNode()
    {
        return empty($this->uri) || preg_match('/^_:/', $this->uri);
    }

    /**
     * Go through the propery values and check if there is one in the specified language.
     * @param string $predicate
     * @param string $language
     * @return bool
     */
    public function hasPropertyInLanguage($predicate, $language)
    {
        foreach ($this->getProperty($predicate) as $value) {
            if ($value instanceof Literal && $value->getLanguage() == $language) {
                return true;
            }
        }
        return false;
    }

    /**
     * Gets the specified property values but filter only those in the specified language.
     * @TODO Rename to getPropertyInLanguage
     * @param string $predicate
     * @param string $language
     * @return RdfObject[]
     */
    public function retrievePropertyInLanguage($predicate, $language)
    {
        $values = [];
        foreach ($this->getProperty($predicate) as $value) {
            if ($value instanceof Literal && $value->getLanguage() === $language) {
                $values[] = $value;
            }
        }
        return $values;
    }

    /**
     * Gets list of all languages that currently exist in the properties of the resource.
     * @TODO Rename to getLanguages
     * @return string[]
     */
    public function retrieveLanguages()
    {
        $languages = [];
        foreach ($this->getProperties() as $property) {
            foreach ($property as $value) {
                if ($value instanceof Literal && $value->getLanguage() !== null &&
                    !isset($languages[$value->getLanguage()])) {
                    $languages[$value->getLanguage()] = true;
                }
            }
        }

        return array_keys($languages);
    }

    /**
     * Gets property value and checks if it is only one.
     * @param string $property
     * @return null|string
     * @throws OpenSkosException
     */
    public function getPropertySingleValue($property)
    {
        $values = $this->getProperty($property);

        if (count($values) > 1) {
            throw new OpenSkosException(
                'Multiple values found for property "' . $property . '" while a single one was requested.'
                . ' Values ' . implode(', ', $values)
            );
        }

        if (!empty($values)) {
            return $values[0];
        } else {
            return null;
        }
    }

    /**
     * Gets property value and implodes it if multiple values are found.
     * @param string $property
     * @param string $language
     * @return string
     */
    public function getPropertyFlatValue($property, $language = null)
    {
        if (!empty($language)) {
            $values = $this->retrievePropertyInLanguage($property, $language);
        } else {
            $values = $this->getProperty($property);
        }

        return implode(', ', $values);
    }

    /**
     * Gets the resource in simple flat array with all (or filtered) properties.
     * @param array $filter , optional
     * @param string $language , optional
     * @return array
     */
    public function toFlatArray($filter = [], $language = null)
    {
        $result = [];

        foreach (array_keys($this->getProperties()) as $property) {
            if (empty($filter) || in_array($property, $filter)) {
                $result[Namespaces::shortenProperty($property)] = $this->getPropertyFlatValue($property, $language);
            }
        }

        // @TODO uri and caption are out of scope here, but really handful.
        if (empty($filter) || in_array('uri', $filter)) {
            $result['uri'] = $this->getUri();
        }
        if (empty($filter) || in_array('caption', $filter)) {
            $result['caption'] = $this->getCaption($language);
        }

        return $result;
    }

    /**
     * Ensures the concept has metadata for tenant, set, creator, date submited, modified and other like this.
     * @param string $tenantUri
     * @param string $setUri
     * @param \OpenSkos2\Person $person
     * @param \OpenSkos2\PersonManager $personManager
     * @param  \OpenSkos2\Rdf\Resource | null $existingResource, optional $existingResource of one of concrete child types used for update
     * override for a concerete resources when necessary
     */
     public function ensureMetadata(
        $tenantUri, 
        $setUri, 
        \OpenSkos2\Person $person, 
        \OpenSkos2\PersonManager $personManager, 
        $existingResource = null)
    {
        $nowLiteral = function () {
            return new Literal(date('c'), null, Literal::TYPE_DATETIME);
        };

        if ($existingResource === null) { // a completely new resource under creation
            $this->setProperty(DcTerms::CREATOR, new Uri($person->getUri())); 
            $this->setProperty(DcTerms::DATESUBMITTED, $nowLiteral());
            if ($this->isPropertyEmpty(OpenSkos::UUID)) {
                $this->setProperty(OpenSkos::UUID, new Literal(Uuid::uuid4()));
            }
        } else {
            $this->setProperty(DcTerms::MODIFIED, $nowLiteral());
            $this->addProperty(DcTerms::CONTRIBUTOR, new Uri($person->getUri()));
            if ($this->getType() != RelationType::TYPE) {
                $this->setProperty(OpenSkos::UUID, $existingResource->getUuid());
            }
            $creators = $existingResource->getProperty(DcTerms::CREATOR);
            if (count($creators) === 0) {
                $this->setProperty(DcTerms::CREATOR, new Literal("Unknown"));
            } else {
                $this->setProperty(DcTerms::CREATOR, $creators[0]);
            }
            $dateSubmitted = $existingResource->getProperty(DcTerms::DATESUBMITTED);
            if (count($dateSubmitted) !== 0) {
                $this->setProperty(
                    DcTerms::DATESUBMITTED,
                    new Literal(
                        $dateSubmitted[0],
                        null,
                        Literal::TYPE_DATETIME
                    )
                );
            }
        }
    }

    /**
     *
     * @return DateTime|null
     */
    //Meertens: for us modified is not alwaus given, so if it absent
    public function getLatestModifyDate()
    {
        $dates = $this->getProperty(Namespaces\DcTerms::MODIFIED);
        if (empty($dates)) {
            return;
        }

        $latestDate = null;
        foreach ($dates as $date) {
            /* @var $date Literal */
            /* @var $dateTime DateTime */
            $dateTime = $date->getValue();
            if (!$latestDate || $dateTime->getTimestamp() > $latestDate->getTimestamp()) {
                $latestDate = $dateTime;
            }
        }

        return $latestDate;
    }

    // TODO: find usages, test and ask picturaa if of no use of buggy
    /**
     * @TODO Separate in StatusAwareResource class or something like that
     * @param string &$predicate
     * @param RdfObject &$value
     */
    protected function handleSpecialProperties(&$predicate, RdfObject &$value)
    {
        // Validation throws an error when not all letters are lowercase while
        // creating or updating an object
        // @TODO find better way and prevent hidden altering of the properties values in the Resource class.
        // Status is always transformed to lowercase.
        if ($predicate === OpenSkos::STATUS) {
            $value->setValue(strtolower($value->getValue()));
        }
    }

   

    public function selfGenerateUri($tenant, $set, $manager)
    {
        $init = $manager->getInitArray();
        if (!$init["custom.default_urigenerate"]) {
            $customGen = new UriGeneration();
            return $customGen->generateUri($manager, $this);
        }
        
        $uuid = Uuid::uuid4();

        if (!$this->isBlankNode()) {
            throw new UriGenerationException(
                'The resource already has an uri. Can not generate new one.'
            );
        }


        $uri = $this->assembleUri($tenant, $set, $uuid, null, $init);


        if ($manager->askForUri($uri, true)) {
            throw new UriGenerationException(
                'The generated uri "' . $uri . '" is already in use.'
            );
        }

        $this->setUri($uri);
        
        $this->setProperty(OpenSkos::UUID, new Literal($uuid));
        
        return $uri;
    }

    // TODO: discuss the rules for generating Uri's for non-concepts
    protected function assembleUri($tenant, $set, $uuid, $notation, $init)
    {
        return $set->getUri() . "/" . $uuid;
    }
}
