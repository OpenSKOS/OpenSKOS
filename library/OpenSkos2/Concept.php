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

use OpenSkos2\Exception\OpenSkosException;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Namespaces\Rdf;
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Rdf\Literal;
use OpenSkos2\Rdf\Resource;
use OpenSkos2\Rdf\Uri;
use OpenSKOS_Db_Table_Row_Tenant;
use OpenSKOS_Db_Table_Tenants;
use Rhumsaa\Uuid\Uuid;

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

    /**
     * Resource constructor.
     * @param string $uri
     */
    public function __construct($uri)
    {
        parent::__construct($uri);
        $this->addProperty(Rdf::TYPE, new Uri(self::TYPE));
    }

    /**
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
     * Check if the concept is deleted
     *
     * @return boolean
     */
    public function isDeleted()
    {
        if ($this->getStatus() === self::STATUS_DELETED) {
            return true;
        }
        return false;
    }

    /**
     * Gets preview title for the concept.
     * @param string $language
     * @return string
     * @throws \Exception
     */
    public function getCaption($language = null)
    {
        return $this->getPropertyFlatValue(Skos::PREFLABEL, $language);
    }
    
    /**
     * Get openskos:uuid if it exists
     *
     * @return string
     */
    public function getUuid()
    {
        $uuids = $this->getProperty(OpenSkos::UUID);
        if (isset($uuids[0])) {
            return $uuids[0]->getValue();
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
     *
     * @return OpenSKOS_Db_Table_Row_Tenant
     */
    public function getInstitution()
    {
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
     * Generates an uri for the concept.
     * Requires a URI from to an openskos collection
     *
     * @return string
     */
    public function selfGenerateUri()
    {
        if (!$this->isBlankNode()) {
            throw new OpenSkosException(
                'The concept already has an uri. Can not generate new one.'
            );
        }
        
        
        // @TODO What is up with collection?
        if ($this->isPropertyEmpty(OpenSkos::SET)) {
            throw new OpenSkosException(
                'Collection uri is required to generate concept uri.'
            );
        }
        
        $collectionUri = $this->getProperty(OpenSkos::SET)[0]->getUri();
        
        if ($this->isPropertyEmpty(Skos::NOTATION)) {
            $uri = self::generateUri($collectionUri);
        } else {
            $uri = self::generateUri(
                $collectionUri,
                $this->getProperty(Skos::NOTATION)[0]->getValue()
            );
        }
        
        $this->setUri($uri);
        return $uri;
    }
    
    /**
     * Generates concept uri from collection and notation
     * @param string $collectionUri
     * @param string $firstNotation, optional. New uuid will be used if empty
     * @return string
     */
    public static function generateUri($collectionUri, $firstNotation = null)
    {
        $separator = '/';
        
        if (empty($firstNotation)) {
            $uri = $collectionUri . $separator . Uuid::uuid4();
        } else {
            $uri = $collectionUri . $separator . $firstNotation;
        }
        
        return $uri;
    }
}
