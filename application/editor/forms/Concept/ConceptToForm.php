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

use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Concept;
use OpenSkos2\ConceptCollection;
use OpenSkos2\Rdf\Literal;
use OpenSkos2\Tenant;
use OpenSkos2\Exception\TenantNotFoundException;
use OpenSkos2\SkosXl\Label;
use OpenSkos2\Concept\LabelHelper;

/**
 * Gets specific data from the concept and prepares it for the Editor_Forms_Concept
 */
class Editor_Forms_Concept_ConceptToForm
{
    /**
     * Get form data for creating new concept based on lang and pref label.
     * @param string $language
     * @param string $prefLabel
     * @return array
     */
    public static function getNewConceptFormData($language, $prefLabel, Tenant $tenant, LabelHelper $labelHelper)
    {
        if ($tenant === null) {
            throw new TenantNotFoundException('Tenant not specified');
        }
        
        $formData = [
            'conceptLanguages' => [
                strtoupper($language) => [
                    strtoupper($language) => $language
                ]
            ],
            'altLabel' => [
                [
                    'languageCode' => $language,
                    'value' => [''],
                ],
            ],
            'hiddenLabel' => [
                [
                    'languageCode' => $language,
                    'value' => [''],
                ],
            ],
        ];
        
        if ($tenant->getEnableSkosXl() === false) {
            $formData['prefLabel'] = [
                [
                    'languageCode' => $language,
                    'value' => [
                        $prefLabel
                    ]
                ]
            ];
        } else {
            $label = $labelHelper->createNewLabel($prefLabel, $language, $tenant);
            
            $formData['skosXlPrefLabel'] = [
                $label->getUri()
            ];
        }
        
        return $formData;
    }
    
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
        self::multiValuedNoLangPropertiesToForm($concept, $formData);
        self::schemesToForm($concept, $formData);
        self::relationsToForm($concept, $formData);
        self::skosXlLabelsToForm($concept, $formData);
        
        $formData['uri'] = $concept->getUri();
        
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
        foreach (Editor_Forms_Concept::getTranslatedFieldsMap() as $field => $property) {
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
        
        self::ensureLabels($concept, $formData);
    }
    
    /**
     * Ensure we have alt label and hidden label everywher
     * @param Concept $concept
     * @param array $formData
     */
    protected static function ensureLabels(Concept $concept, &$formData)
    {
        $translatedFieldsMap = Editor_Forms_Concept::getTranslatedFieldsMap();
        
        foreach ($concept->retrieveLanguages() as $language) {
            if (!$concept->hasPropertyInLanguage(Skos::PREFLABEL, $language)) {
                $formData[array_search(Skos::PREFLABEL, $translatedFieldsMap)][] = [
                    'languageCode' => $language,
                    'value' => [''],
                ];
            }
            if (!$concept->hasPropertyInLanguage(Skos::ALTLABEL, $language)) {
                $formData[array_search(Skos::ALTLABEL, $translatedFieldsMap)][] = [
                    'languageCode' => $language,
                    'value' => [''],
                ];
            }
            if (!$concept->hasPropertyInLanguage(Skos::HIDDENLABEL, $language)) {
                $formData[array_search(Skos::HIDDENLABEL, $translatedFieldsMap)][] = [
                    'languageCode' => $language,
                    'value' => [''],
                ];
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
        foreach (Editor_Forms_Concept::getFlatFieldsMap() as $field => $property) {
            // @TODO Should fail if having more than one value.
            $formData[$field] = $concept->getPropertyFlatValue($property);
        }
    }
    
    /**
     * Properties like pref label, alt label etc.
     * @param Concept $concept
     * @param array &$formData
     */
    protected static function multiValuedNoLangPropertiesToForm(Concept $concept, &$formData)
    {
        foreach (Editor_Forms_Concept::multiValuedNoLangFieldsMap() as $field => $property) {
            $formData[$field] = [];
            foreach ($concept->getProperty($property) as $value) {
                $formData[$field][] = $value->getValue();
            }
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
        $conceptManager = self::getDI()->get('OpenSkos2\ConceptManager');
        
        foreach (Editor_Forms_Concept::getPerSchemeRelationsMap() as $relationKey => $relationProperty) {
            $relations = $conceptManager->fetchByUris(
                $concept->getProperty($relationProperty)
            );
            
            foreach ($concept->getProperty(Skos::INSCHEME) as $scheme) {
                $formData[$relationKey][$scheme->getUri()] = new ConceptCollection();
            }
            
            foreach ($relations as $relation) {
                foreach ($relation->getProperty(Skos::INSCHEME) as $scheme) {
                    if (isset($formData[$relationKey][$scheme->getUri()])) {
                        $formData[$relationKey][$scheme->getUri()]->append($relation);
                    }
                }
            }
        }
        
        foreach (Editor_Forms_Concept::getSchemeIndependentRelationsMap() as $relationKey => $relationProperty) {
            $formData[$relationKey][] = $conceptManager->fetchByUris($concept->getProperty($relationProperty));
        }
    }
    
    /**
     * Skos xl labels to form.
     * @param Concept $concept
     * @param array &$formData
     */
    protected static function skosXlLabelsToForm(Concept $concept, &$formData)
    {
        foreach (Editor_Forms_Concept::getSkosXlLablesMap() as $field => $property) {
            foreach ($concept->getProperty($property) as $value) {
                $formData[$field][] = $value->getUri();
            }
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
