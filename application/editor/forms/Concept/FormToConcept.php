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
use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Concept;
use OpenSkos2\Rdf\Literal;
use OpenSkos2\Rdf\Uri;

/**
 * Gets specific data from the concept and prepares it for the Editor_Forms_Concept
 */
class Editor_Forms_Concept_FormToConcept
{
    /**
     * Gets specific data from the concept and prepares it for the Editor_Forms_Concept
     * @param Concept &$concept
     * @param array $formData
     * @param OpenSKOS_Db_Table_Row_User $user
     * @return array
     */
    public static function toConcept(Concept &$concept, $formData, OpenSKOS_Db_Table_Row_User $user)
    {
        self::translatedPropertiesToConcept($concept, $formData);
        self::flatPropertiesToConcept($concept, $formData);
        self::resourcesToConcept($concept, $formData);
        self::metaDataToConcept($concept, $formData, $user);
    }
    
    /**
     * Properties like pref label, alt label etc.
     * @param Concept &$concept
     * @param array $formData
     */
    protected static function translatedPropertiesToConcept(Concept &$concept, $formData)
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
            if (!empty($formData[$field])) {
                $propertyValues = [];
                foreach ($formData[$field] as $language => $values) {
                    foreach ($values as $value) {
                        if (!empty($value)) {
                            $propertyValues[] = new Literal($value, $language);
                        }
                    }
                }
                $concept->setProperties($property, $propertyValues);
            }
        }
    }
    
    /**
     * Properties like pref label, alt label etc.
     * @param Concept &$concept
     * @param array $formData
     */
    protected static function flatPropertiesToConcept(Concept &$concept, $formData)
    {
        $flatFields = [
            'status' => OpenSkos::STATUS,
            'notation' => Skos::NOTATION, // @TODO array
            'uuid' => OpenSkos::UUID, // @TODO Readonly/generated
            'toBeChecked' => OpenSkos::TOBECHECKED,
        ];
        foreach ($flatFields as $field => $property) {
            if (!empty($formData[$field])) {
                $concept->setProperty($property, new Literal($formData[$field]));
            }
            
            // @TODO Delete property if no value
        }
    }
    
    /**
     * Schemes and relations to concept
     * @param Concept &$concept
     * @param array $formData
     */
    protected static function resourcesToConcept(Concept &$concept, $formData)
    {
        $fieldToUris = function ($value) {
            $uris = [];
            if (!empty($value)) {
                foreach ($value as $uri) {
                    if (!empty($uri)) {
                        $uris[] = new Uri($uri);
                    }
                }
            }
            return $uris;
        };
        
        $resourcesProperties = [
            'inScheme' => Skos::INSCHEME,
            'topConceptOf' => Skos::TOPCONCEPTOF,
            
            'narrower' => Skos::NARROWER,
            'broader' => Skos::BROADER,
            'related' => Skos::RELATED,
            
            'broadMatch' => Skos::BROADMATCH,
            'narrowMatch' => Skos::NARROWMATCH,
            'relatedMatch' => Skos::RELATEDMATCH,
            'mappingRelation' => Skos::MAPPINGRELATION,
            'closeMatch' => Skos::CLOSEMATCH,
            'exactMatch' => Skos::EXACTMATCH,
        ];
        
        foreach ($resourcesProperties as $field => $property) {
            if (isset($formData[$field])) {
                $concept->setProperties($property, $fieldToUris($formData[$field]));
            } else {
                $concept->unsetProperty($property);
            }
        }
        
        // @TODO Delete relation from both.
        // @TODO Remove topConceptOf for schemes in which it is not already part
    }
    
    /**
     * Per scheme relations + mapping properties.
     * @param Concept &$concept
     * @param array $formData
     * @param OpenSKOS_Db_Table_Row_User $user
     */
    protected static function metaDataToConcept(Concept &$concept, $formData, OpenSKOS_Db_Table_Row_User $user)
    {
        // @TODO on import and post as well!
        $forFirstTimeInEditor = [
            OpenSkos::TENANT => new Literal($user->tenant),
            OpenSkos::SET => new Uri('http:://todo/gtaa'),
            DcTerms::CREATOR => $user->getFoafPerson(),
            DcTerms::DATESUBMITTED => new Literal(date('c'), null, \OpenSkos2\Rdf\Literal::TYPE_DATETIME),
        ];
        
        foreach ($forFirstTimeInEditor as $property => $defaultValue) {
            if (!$concept->hasProperty($property)) {
                $concept->setProperty($property, $defaultValue);
            }
        }
        
        $nowLiteral = function () {
            return new Literal(date('c'), null, \OpenSkos2\Rdf\Literal::TYPE_DATETIME);
        };
        
        $concept->setProperty(DcTerms::CONTRIBUTOR, $user->getFoafPerson());
        $concept->setProperty(DcTerms::MODIFIED, $nowLiteral());
        
        if ($formData['status'] == OpenSKOS_Concept_Status::APPROVED &&
                ($concept->getStatus() != OpenSKOS_Concept_Status::APPROVED)) {
            $concept->setProperty(OpenSkos::ACCEPTEDBY, $user->getFoafPerson());
            $concept->setProperty(DcTerms::DATEACCEPTED, $nowLiteral());
        }
        
        if (OpenSKOS_Concept_Status::isStatusLikeDeleted($formData['status'])) {
            $concept->setProperty(OpenSkos::DELETEDBY, $user->getFoafPerson());
            $concept->setProperty(OpenSkos::DATE_DELETED, $nowLiteral());
        }
    }
}
