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
        $oldStatus = $concept->getStatus();
        
        self::translatedPropertiesToConcept($concept, $formData);
        self::flatPropertiesToConcept($concept, $formData);
        self::resourcesToConcept($concept, $formData);
        self::metadataToConcept($concept, $user, $oldStatus);
    }
    
    /**
     * Properties like pref label, alt label etc.
     * @param Concept &$concept
     * @param array $formData
     */
    protected static function translatedPropertiesToConcept(Concept &$concept, $formData)
    {
        foreach (Editor_Forms_Concept::getTranslatedFieldsMap() as $field => $property) {
            if (!empty($formData[$field])) {
                $propertyValues = [];
                foreach ($formData[$field] as $language => $values) {
                    if (is_string($language)) { // If int - it is a template
                        foreach ($values as $value) {
                            if (!empty($value)) {
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
        
        foreach (Editor_Forms_Concept::getResourceBasedFieldsMap() as $field => $property) {
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
     * @param OpenSKOS_Db_Table_Row_User $user
     * @param string $oldStatus
     */
    protected static function metadataToConcept(
        Concept &$concept,
        OpenSKOS_Db_Table_Row_User $user,
        $oldStatus
    ) {
        $concept->ensureMetadata(
            $user->tenant,
            new Uri('http://todo/gtaa'),
            $user->getFoafPerson(),
            $oldStatus
        );
    }
}
