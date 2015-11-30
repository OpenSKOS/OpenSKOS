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
use OpenSkos2\Namespaces\OpenSkos;

/**
 * Gets specific data from the concept scheme and prepares it for the Editor_Forms_ConceptScheme
 */
class Editor_Forms_ConceptScheme_ConceptSchemeToForm
{
    /**
     * Gets specific data from the concept scheme and prepares it for the Editor_Forms_ConceptScheme
     * @param ConceptScheme $conceptScheme
     * @return array
     */
    public static function toFormData(ConceptScheme $conceptScheme)
    {
        $formData = [];
                
        self::languagesToForm($conceptScheme, $formData);
        self::translatedPropertiesToForm($conceptScheme, $formData);
        self::uriToForm($conceptScheme, $formData);
        self::collectionToForm($conceptScheme, $formData);
        
        return $formData;
    }
    
    /**
     * Languages tabs
     * @param ConceptScheme $conceptScheme
     * @param array &$formData
     */
    protected static function languagesToForm(ConceptScheme $conceptScheme, &$formData)
    {
        $formData['conceptLanguages'] = [];
        foreach ($conceptScheme->retrieveLanguages() as $language) {
            $formData['conceptLanguages'][strtoupper($language)] = [strtoupper($language) => $language];
        }
    }
    
    /**
     * Properties like pref label, alt label etc.
     * @param ConceptScheme $conceptScheme
     * @param array &$formData
     */
    protected static function translatedPropertiesToForm(ConceptScheme $conceptScheme, &$formData)
    {
        foreach (Editor_Forms_ConceptScheme::getTranslatedFieldsMap() as $field => $property) {
            $groupedValues = [];
            
            foreach ($conceptScheme->getProperty($property) as $value) {
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
     * Splits concept scheme uri.
     * @param ConceptScheme $conceptScheme
     * @param array &$formData
     */
    protected static function uriToForm(ConceptScheme $conceptScheme, &$formData)
    {
        $set = (string)$conceptScheme->getPropertySingleValue(OpenSkos::SET);
        $formData['uriBase'] = $set;
        $formData['uriCode'] = str_replace($set, '', $conceptScheme->getUri());
    }
    
    /**
     * @param ConceptScheme $conceptScheme
     * @param array &$formData
     */
    protected static function collectionToForm(ConceptScheme $conceptScheme, &$formData)
    {
        $formData['collection'] = (string)$conceptScheme->getPropertySingleValue(OpenSkos::SET);
    }
}
