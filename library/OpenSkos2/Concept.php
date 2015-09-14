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

use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Namespaces\Rdf;
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Rdf\Resource;
use OpenSkos2\Rdf\Uri;
use OpenSkos2\Exception\OpenSkosException;

class Concept extends Resource
{
    const TYPE = 'http://www.w3.org/2004/02/skos/core#Concept';

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
     * Gets preview title for the concept.
     * @param string $language
     * @return string
     * @throws \Exception
     */
    public function getPreviewTitle($language = null)
    {
        if (!empty($lanaguage)) {
            $values = $this->retrievePropertyInLanguage(Skos::PREFLABEL, $language);
        } else {
            $values = $this->getProperty(Skos::PREFLABEL);
        }
        
        return implode(', ', $values);
    }
    
    /**
     * Generates an uri for the concept.
     */
    public function selfGenerateUri()
    {
        if (!$this->isBlankNode()) {
            throw new OpenSkosException(
                'The concept already has an uri. Can not generate new one.'
            );
        }
        
        if ($this->isPropertyEmpty(Skos::COLLECTION)) {
            throw new OpenSkosException(
                'Collection uri is required to generate concept uri.'
            );
        }
        
        $collectionUri = $this->getProperty(Skos::COLLECTION)[0]->getUri();
        
        if ($this->isPropertyEmpty(Skos::NOTATION)) {
            $uri = self::generateUri($collectionUri);
        } else {
            $uri = self::generateUri(
                $collectionUri,
                $this->getProperty(Skos::NOTATION)[0]->getValue()
            );
        }
        
        $this->setUri($uri);
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
            $uri = $collectionUri . $separator . \Rhumsaa\Uuid\Uuid::uuid4();
        } else {
            $uri = $collectionUri . $separator . $firstNotation;
        }
        
        return $uri;
    }
}
