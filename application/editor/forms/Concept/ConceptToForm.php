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

use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Concept;
use OpenSkos2\Rdf\Literal;

/**
 * Gets specific data from the concept and prepares it for the Editor_Forms_Concept
 */
class Editor_Forms_Concept_ConceptToForm
{
    /**
     * Gets specific data from the concept and prepares it for the Editor_Forms_Concept
     * @param Concept $concept
     * @return array
     */
    public static function toFormData(Concept $concept)
    {
        $formData = [];
                
        self::languagesToForm($concept, $formData);
        self::translatedPropertiesToForm($concept, $formData);
        self::flatPropertiesToForm($concept, $formData);
        self::schemesToForm($concept, $formData);
        self::relationsToForm($concept, $formData);
        
        return $formData;
    }
    
    /**
     * Languages tabs
     * @param Concept $concept
     * @param array &$formData
     */
    protected static function languagesToForm(Concept $concept, &$formData)
    {
        $formData['conceptLanguages'] = [];
        foreach ($concept->retrieveLanguages() as $language) {
            $formData['conceptLanguages'][strtoupper($language)] = [strtoupper($language) => $language];
        }
    }
    
    /**
     * Properties like pref label, alt label etc.
     * @param Concept $concept
     * @param array &$formData
     */
    protected static function translatedPropertiesToForm(Concept $concept, &$formData)
    {
        $translatedProperties = [
            'prefLabel' => Skos::PREFLABEL,
            'altLabel' => Skos::ALTLABEL,
            'hiddenLabel' => Skos::HIDDENLABEL,
            'changeNote' => Skos::CHANGENOTE,
            'definition' => Skos::DEFINITION,
            'editorialNote' => Skos::EDITORIALNOTE,
            'example' => Skos::EXAMPLE,
            'historyNote' => Skos::HISTORYNOTE,
            'note' => Skos::NOTE,
            'scopeNote' => Skos::SCOPENOTE,
        ];
        foreach ($translatedProperties as $field => $property) {
            $groupedValues = [];
            
            foreach ($concept->getProperty($property) as $value) {
                if ($value instanceof Literal && $value->getLanguage()) {
                    if (!isset($groupedValues[$value->getLanguage()])) {
                        $groupedValues[$value->getLanguage()] = [
                            'languageCode' => $value->getLanguage(),
                            'value' => [],
                        ];
                    }
                    
                    $groupedValues[$value->getLanguage()]['value'][] = $value->getValue();
                } else {
                    throw new \Exception(
                        'Value ' . $value . ' from field ' . $field . ' is not translated.'
                    );
                }
            }
            
            if (!empty($groupedValues)) {
                $formData[$field] = array_values($groupedValues);
            }
        }
    }
    
    /**
     * Properties like pref label, alt label etc.
     * @param Concept $concept
     * @param array &$formData
     */
    protected static function flatPropertiesToForm(Concept $concept, &$formData)
    {
        $flatFields = [
            'status' => OpenSkos::STATUS,
            'notation' => Skos::NOTATION,
            'uuid' => OpenSkos::UUID,
            'toBeChecked' => OpenSkos::TOBECHECKED,
        ];
        foreach ($flatFields as $field => $property) {
            $formData[$field] = $concept->getPropertyFlatValue($property);
        }
    }
    
    /**
     * Scheme tabs and top concept of.
     * @param Concept $concept
     * @param array &$formData
     */
    protected static function schemesToForm(Concept $concept, &$formData)
    {
        $formData['inScheme'] = [];
        $conceptSchemesCaptions = self::getDI()->get('Editor_Models_ConceptSchemesCache')
            ->fetchUrisCaptionsMap();
        foreach ($concept->getProperty(Skos::INSCHEME) as $schemeUri) {
            $schemeUri = (string) $schemeUri;
            $caption = $conceptSchemesCaptions[$schemeUri];
            $formData['inScheme'][$caption] = [$caption => $schemeUri];
        }
        
        $formData['topConceptOf'] = array_map('strval', $concept->getProperty(Skos::TOPCONCEPTOF));
    }
    
    /**
     * Per scheme relations + mapping properties.
     * @param Concept $concept
     * @param array &$formData
     */
    protected static function relationsToForm(Concept $concept, &$formData)
    {
        $conceptManager = self::getDI()->get('\OpenSkos2\ConceptManager');
        
        $perSchemeRelations = [
            'narrower' => Skos::NARROWER,
            'broader' => Skos::BROADER,
            'related' => Skos::RELATED,
        ];
        foreach ($perSchemeRelations as $relationKey => $relationType) {
            foreach ($concept->getProperty(Skos::INSCHEME) as $scheme) {
                $formData[$relationKey][$scheme->getUri()] = $conceptManager->fetchRelations(
                    $concept->getUri(),
                    $relationType,
                    $scheme->getUri()
                );
            }
        }
        
        $schemeIndependentRelations = array(
            'broadMatch' => Skos::BROADMATCH,
            'narrowMatch' => Skos::NARROWMATCH,
            'relatedMatch' => Skos::RELATEDMATCH,
            'mappingRelation' => Skos::MAPPINGRELATION,
            'closeMatch' => Skos::CLOSEMATCH,
            'exactMatch' => Skos::EXACTMATCH,
        );
        foreach ($schemeIndependentRelations as $relationKey => $relationType) {
            $formData[$relationKey][] = $conceptManager->fetchRelations(
                $concept->getUri(),
                $relationType
            );
        }
    }
    
    /**
     * Get dependency injection container
     * 
     * @return \DI\Container
     */
    protected static function getDI()
    {
        return Zend_Controller_Front::getInstance()->getDispatcher()->getContainer();
    }
}
