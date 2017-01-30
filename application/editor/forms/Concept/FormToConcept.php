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
     * @param OpenSKOS_Db_Table_Row_User $set
     * @param OpenSKOS_Db_Table_Row_User $user
     * @return array
     */
    public static function toConcept(
        Concept &$concept,
        $formData,
        OpenSKOS_Db_Table_Row_Set $set,
        OpenSKOS_Db_Table_Row_User $user
    ) {
        $oldStatus = $concept->getStatus();
        
        self::translatedPropertiesToConcept($concept, $formData);
        self::flatPropertiesToConcept($concept, $formData);
        self::multiValuedNoLangPropertiesToConcept($concept, $formData);
        self::resourcesToConcept($concept, $formData);
        self::metadataToConcept($concept, $set, $user, $oldStatus);
    }
    
    /**
     * Properties like pref label, alt label etc.
     * @param Concept &$concept
     * @param array $formData
     */
    protected static function translatedPropertiesToConcept(Concept &$concept, $formData)
    {
        foreach (Editor_Forms_Concept::getTranslatedFieldsMap() as $field => $property) {
            if (!self::emptyStringOrNull($formData[$field])) {
                $propertyValues = [];
                foreach ($formData[$field] as $language => $values) {
                    if (is_string($language)) { // If int - it is a template
                        foreach ($values as $value) {
                            if (!self::emptyStringOrNull($value)) {
                                $propertyValues[] = new Literal($value, $language);
                            }
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
        foreach (Editor_Forms_Concept::getFlatFieldsMap() as $field => $property) {
            if (!self::emptyStringOrNull($formData[$field])) {
                $concept->setProperty($property, new Literal($formData[$field]));
            } else {
                $concept->unsetProperty($property);
            }
        }
    }
    
    /**
     * Properties like pref label, alt label etc.
     * @param Concept $concept
     * @param array &$formData
     */
    protected static function multiValuedNoLangPropertiesToConcept(Concept &$concept, $formData)
    {
        foreach (Editor_Forms_Concept::multiValuedNoLangFieldsMap() as $field => $property) {
            $concept->unsetProperty($property);
            if (!self::emptyStringOrNull($formData[$field])) {
                $values = array_filter(array_map('trim', $formData[$field]));
                foreach ($values as $value) {
                    $concept->addProperty($property, new Literal($value));
                }
            }
        }
    }
    
    /**
     * Schemes and relations to concept
     * @param Concept &$concept
     * @param array $formData
     */
    protected static function resourcesToConcept(Concept &$concept, $formData)
    {
        // @TODO Select "asserted only" on update. Else after first update the inferred
        // relations will get explicitly declared (asserted). Then unset can be removed as well.
        self::unsetAllRelations($concept);
        
        $fieldToUris = function ($value) {
            $uris = [];
            if (!self::emptyStringOrNull($value)) {
                foreach ($value as $uri) {
                    if (!self::emptyStringOrNull($uri)) {
                        $uris[] = new Uri($uri);
                    }
                }
            }
            return $uris;
        };
        
        foreach (Editor_Forms_Concept::getResourceBasedFieldsMap() as $field => $property) {
            if (isset($formData[$field])) {
                $concept->setProperties($property, $fieldToUris($formData[$field]));
            } else {
                $concept->unsetProperty($property);
            }
        }
        
        self::filterTopConceptOf($concept);
    }
    
    /**
     * Clear all relations of the concept before setting the relations from the form.
     * This will remove any hidden, inferred relations like boraderTransitive and narrowerTransitive.
     * If the relation does not come from the form - we don't want it.
     * @param Concept $concept
     */
    protected static function unsetAllRelations(Concept &$concept)
    {
        foreach (Skos::getSkosRelations() as $relationType) {
            $concept->unsetProperty($relationType);
        }
    }
    
    /**
     * Remove all top concept of for schemes which the concept is not inScheme.
     * @param Concept $concept
     */
    protected static function filterTopConceptOf(Concept &$concept)
    {
        $filteredTopConceptOf = [];
        foreach ($concept->getProperty(Skos::INSCHEME) as $schemeUri) {
            if ($concept->isTopConceptOf($schemeUri)) {
                $filteredTopConceptOf[] = $schemeUri;
            }
        }
        $concept->setProperties(Skos::TOPCONCEPTOF, $filteredTopConceptOf);
    }
    
    /**
     * Per scheme relations + mapping properties.
     * @param Concept &$concept
     * @param OpenSKOS_Db_Table_Row_Set $set
     * @param OpenSKOS_Db_Table_Row_User $user
     * @param string $oldStatus
     */
    protected static function metadataToConcept(
        Concept &$concept,
        OpenSKOS_Db_Table_Row_Set $set,
        OpenSKOS_Db_Table_Row_User $user,
        $oldStatus
    ) {
        $concept->ensureMetadata(
            $user->tenant,
            $set->getUri(),
            $user->getFoafPerson(),
            $oldStatus
        );
    }
    
    /**
     * Checks if the value is empty string or null.
     * @param mixed $value
     * @return bool
     */
    protected static function emptyStringOrNull($value)
    {
        return $value === null || $value === '';
    }
}
