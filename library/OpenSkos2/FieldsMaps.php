<?php

/*
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
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Namespaces\SkosXl;
use OpenSkos2\Namespaces\DcTerms;

class FieldsMaps
{
    // @TODO Move to correct namespace/context
    
    /**
     * Gets map of fields to property uris.
     * @return array
     */
    public static function getOldToProperties()
    {
        // @TODO Ensure all fields
        
        return [
            'status' => OpenSkos::STATUS,
            'tenant' => OpenSkos::TENANT,
            'collection' => OpenSkos::SET,
            'uuid' => OpenSkos::UUID,
            'notation' => Skos::NOTATION,
            'inScheme' => Skos::INSCHEME,
            
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
            
            'related' => Skos::RELATED,
            
            'broader' => Skos::BROADER,
            'broaderTransitive' => Skos::BROADERTRANSITIVE,
            'narrower' => Skos::NARROWER,
            'narrowerTransitive' => Skos::NARROWERTRANSITIVE,
            'related' => Skos::RELATED,
            
            'broadMatch' => Skos::BROADMATCH,
            'closeMatch' => Skos::CLOSEMATCH,
            'exactMatch' => Skos::EXACTMATCH,
            'mappingRelation' => Skos::MAPPINGRELATION,
            'narrowMatch' => Skos::NARROWMATCH,
            'relatedMatch' => Skos::RELATEDMATCH,
            
            'topConceptOf' => Skos::TOPCONCEPTOF,
            
            'created_timestamp' => DcTerms::CREATED,
            'modified_timestamp' => DcTerms::MODIFIED,
            'dcterms_dateAccepted' => DcTerms::DATEACCEPTED,
            'dcterms_modified' => DcTerms::MODIFIED,
            'dcterms_creator' => DcTerms::CREATOR,
            'dcterms_dateSubmitted' => DcTerms::DATESUBMITTED,
            'dcterms_contributor' => DcTerms::CONTRIBUTOR,
            
            'dcterms_title' => DcTerms::TITLE,
            
            'skosXlPrefLabel' => SkosXl::PREFLABEL,
            'skosXlAltLabel' => SkosXl::ALTLABEL,
            'skosXlHiddenLabel' => SkosXl::HIDDENLABEL,
        ];
    }
    
    /**
     * Returns the corresposing property for the given field.
     * If property not found - returns $field.
     * @param string $field
     * @return string
     */
    public static function resolveOldField($field)
    {
        $map = self::getOldToProperties();
        if (isset($map[$field])) {
            return $map[$field];
        } else {
            return $field;
        }
    }
}
