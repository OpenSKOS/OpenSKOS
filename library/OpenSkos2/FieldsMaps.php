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

class FieldsMaps {
    
    /* The field mapping: copied from migration script, thsi part differs getOldToProperties and getNamesToProperties method
     * 
     * 
     *  'modified_by' => DcTerms::CONTRIBUTOR,
        'created_by' => DcTerms::CREATOR,
        'dcterms_creator' => DcTerms::CREATOR, ?? not in the old openskos anyway
        'approved_by' => OpenSkos::ACCEPTEDBY,
        'deleted_by' => OpenSkos::DELETEDBY,
       // Olha: the next two filed are added because no timestam and modified_timestamp in jena's output
        'timestamp' => DcTerms::DATESUBMITTED,
        'modified_timestamp' => DcTerms::MODIFIED,
     */

private static function getNamesToPropertiesCommon() {
    return [
            'status' => OpenSkos::STATUS,
            'tenant' => OpenSkos::TENANT,
            'collection' => OpenSkos::SET,
            'uuid' => OpenSkos::UUID,
            'date_deleted' => OpenSkos::DATE_DELETED,
            
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
            
            'dcterms_dateAccepted' => DcTerms::DATEACCEPTED,
            'dcterms_title' => DcTerms::TITLE,
            
            'skosXlPrefLabel' => SkosXl::PREFLABEL,
            'skosXlAltLabel' => SkosXl::ALTLABEL,
            'skosXlHiddenLabel' => SkosXl::HIDDENLABEL,
        ];
}

    // @TODO Move to correct namespace/context
    
    /**
     * Gets map of fields to property uris.
     * @return array
     */
    // Olha: should not be necessary after migration.
    public static function getOldToProperties()
    {
        $common = self::getNamesToPropertiesCommon();
        $add = [ // there is also some crap from the previos code: some fields are apparently duplicated
            'created_by' => DcTerms::CREATOR,
            'dcterms_creator' => DcTerms::CREATOR,
            'timestamp' => DcTerms::DATESUBMITTED,
            'created_timestamp' => DcTerms::CREATED,
            'dcterms_dateSubmitted' => DcTerms::DATESUBMITTED,
            'modified_by' => DcTerms::CONTRIBUTOR,
            'dcterms_contributor' => DcTerms::CONTRIBUTOR,
            'modified_timestamp' => DcTerms::MODIFIED,
            'dcterms_modified' => DcTerms::MODIFIED,
            'approved_by' => OpenSkos::ACCEPTEDBY,
            'dcterms_dateAccepted' => DcTerms::DATEACCEPTED,
            'deleted_by' => OpenSkos::DELETEDBY,
        ];
        return array_merge($common, $add);
    }
    
    // some old openskos-fields are replaced with eqivalent dcterms or to be in-line with dcterms
    
    public static function getNamesToProperties()
    {
        $common = self::getNamesToPropertiesCommon();
        $add = [
            // taken from Solr's document.php
            'creator' => DcTerms::CREATOR,
            'dateSubmitted' => DcTerms::DATESUBMITTED,
            'contributor' => DcTerms::CONTRIBUTOR,
            'modified' => DcTerms::MODIFIED,
            'acceptedBy' => OpenSkos::ACCEPTEDBY,
            'dateAccepted' => DcTerms::DATEACCEPTED,
            'deletedBy' => OpenSkos::DELETEDBY,
            'dateDeleted' => OpenSkos::DATE_DELETED,
        ];
        return array_merge($common, $add);
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
