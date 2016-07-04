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

use OpenSkos2\ConceptScheme;
use OpenSkos2\Rdf\Literal;
use OpenSkos2\Rdf\Uri;
use OpenSkos2\Namespaces\OpenSkos;

/**
 * Gets specific data from the concept scheme and prepares it for the Editor_Forms_ConceptScheme
 */
class Editor_Forms_ConceptScheme_FormToConceptScheme
{
    /**
     * Gets specific data from the concept scheme and prepares it for the Editor_Forms_ConceptScheme
     * @param ConceptScheme &$conceptScheme
     * @param array $formData
     * @param OpenSKOS_Db_Table_Row_User $user
     * @return array
     */
    public static function toConceptScheme(
        ConceptScheme &$conceptScheme,
        $formData,
        OpenSKOS_Db_Table_Row_User $user
    ) {
        self::translatedPropertiesToConceptScheme($conceptScheme, $formData);
        self::metadataToConceptScheme($conceptScheme, $formData, $user);
        self::uriToConceptScheme($conceptScheme, $formData);
        self::collectionToConceptScheme($conceptScheme, $formData);
    }
    
    /**
     * Properties like pref label, alt label etc.
     * @param ConceptScheme &$conceptScheme
     * @param array $formData
     */
    protected static function translatedPropertiesToConceptScheme(ConceptScheme &$conceptScheme, $formData)
    {
        foreach (Editor_Forms_ConceptScheme::getTranslatedFieldsMap() as $field => $property) {
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
                $conceptScheme->setProperties($property, $propertyValues);
            }
        }
    }
    
    /**
     * Per scheme relations + mapping properties.
     * @param ConceptScheme &$conceptScheme
     * @param array $formData
     * @param OpenSKOS_Db_Table_Row_User $user
     */
    protected static function metadataToConceptScheme(
        ConceptScheme &$conceptScheme,
        $formData,
        OpenSKOS_Db_Table_Row_User $user
    ) {
        $conceptScheme->ensureMetadata(
            $user->tenant,
            new Uri($formData['collection']),
            $user->getFoafPerson()
        );
    }
    
    /**
     * Sets concept scheme uri.
     * @param ConceptScheme &$conceptScheme
     * @param array $formData
     */
    protected static function uriToConceptScheme(ConceptScheme &$conceptScheme, $formData)
    {
        $conceptScheme->setUri(
            rtrim($formData['uriBase'], '/') . '/' . $formData['uriCode']
        );
    }
    
    /**
     * Sets concept scheme uri.
     * @param ConceptScheme &$conceptScheme
     * @param array $formData
     */
    protected static function collectionToConceptScheme(ConceptScheme &$conceptScheme, $formData)
    {
        $conceptScheme->setProperty(OpenSkos::SET, new Uri($formData['collection']));
    }
}
