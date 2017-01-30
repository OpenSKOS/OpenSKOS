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
use OpenSkos2\Namespaces\Dc;

class FieldsMaps {

//Meertens: the Picturae's function  getOldToProperties is renamed getNamesToProperties
// because it is used not for mapping old names to properties but just to map
// short names to the corresponding property uri's.
// Also old field names like 'created_timestamp', 'modified_timestamp' is removed, 
// everything which deals with old settings is located in the migration script.
// We use 'dcterms_created' and 'dcterms_modified'  insetad of 'created_timestamp' and 'modified_timestamp'
// the method ResolveOldFields is removed 
  public static function getNamesToProperties() {
    return [

      'status' => OpenSkos::STATUS,
      'tenant' => OpenSkos::TENANT,
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
      'dcterms_modified' => DcTerms::MODIFIED,
      'dcterms_creator' => DcTerms::CREATOR,
      'dcterms_dateSubmitted' => DcTerms::DATESUBMITTED,
      'dcterms_contributor' => DcTerms::CONTRIBUTOR,
      'dc_contributor' => Dc::CONTRIBUTOR,
      'dcterms_title' => DcTerms::TITLE,
      'skosXlPrefLabel' => SkosXl::PREFLABEL,
      'skosXlAltLabel' => SkosXl::ALTLABEL,
      'skosXlHiddenLabel' => SkosXl::HIDDENLABEL,
      'inSkosCollection' => OpenSkos::INSKOSCOLLECTION,
      'member' => Skos::MEMBER,
      'set' => OpenSkos::SET,
      'creator' => DcTerms::CREATOR,
      'dateSubmitted' => DcTerms::DATESUBMITTED,
      'contributor' => DcTerms::CONTRIBUTOR,
      'modified' => DcTerms::MODIFIED,
      'acceptedBy' => OpenSkos::ACCEPTEDBY,
      'dateAccepted' => DcTerms::DATEACCEPTED,
      'deletedBy' => OpenSkos::DELETEDBY,
      'dateDeleted' => OpenSkos::DATE_DELETED,
    ];
  }

}
