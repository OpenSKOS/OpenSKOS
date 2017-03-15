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
use OpenSkos2\Namespaces\vCard;
use OpenSkos2\Namespaces\Rdf;

class FieldsMaps {

// Meertens: in Meertens branch this class is not used to support old names but to map short names to URI's 
// in the OpenSKOS2. So that's why the following changes have happened:
// The Picturae's function  "getOldToProperties" is renamed to "getNamesToProperties"
// because it is used not for mapping old names to properties but just to map
// short names to the corresponding property uri's.
// Also old field names like 'created_timestamp', 'modified_timestamp' are removed, 
// everything which deals with old settings is located in the migration script (as far as I remember and have it planned:) ).
// We use 'dcterms_created' and 'dcterms_modified'  insetad of 'created_timestamp' and 'modified_timestamp'
// the method ResolveOldFields is removed 
  public static function getNamesToProperties() {
    return [
      'status' => OpenSkos::STATUS,
      'tenant' => OpenSkos::TENANT,
      'set' => OpenSkos::SET,
      'uuid' => OpenSkos::UUID,
      'notation' => Skos::NOTATION,
      'inScheme' => Skos::INSCHEME,
      'inSkosCollection' => OpenSkos::INSKOSCOLLECTION,
      'enableStatussesSystem' => OpenSkos::ENABLESTATUSSESSYSTEMS,
      'disableSearchInOtherTenants' => OpenSkos::DISABLESEARCHINOTERTENANTS,
      'code' => OpenSkos::CODE,
      'conceptBaseUri' => OpenSkos::CONCEPTBASEURI,
      'allow_oai' => OpenSkos::ALLOW_OAI,
      'webpage' => OpenSkos::WEBPAGE,
      'OAI_base_uri' => OpenSkos::CONCEPTBASEURI,
      
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
      'member' => Skos::MEMBER,
      'hasTopConcept' => Skos::HASTOPCONCEPT,
      
      'dcterms_dateSubmitted' => DcTerms::DATESUBMITTED,
      'dcterms_dateAccepted' => DcTerms::DATEACCEPTED,
      'dcterms_modified' => DcTerms::MODIFIED,
      'dcterms_creator' => DcTerms::CREATOR,
      'dcterms_contributor' => DcTerms::CONTRIBUTOR,
      'dcterms_title' => DcTerms::TITLE,
      'dcterms_publisher'=>DcTerms::PUBLISHER,
      'dcterms_license'=>DcTerms::LICENSE,
      'dcterms_description'=>DcTerms::DESCRIPTION,
      
      'skosXlPrefLabel' => SkosXl::PREFLABEL,
      'skosXlAltLabel' => SkosXl::ALTLABEL,
      'skosXlHiddenLabel' => SkosXl::HIDDENLABEL,
      
      'dateSubmitted' => DcTerms::DATESUBMITTED,
      'modified' => DcTerms::MODIFIED,
      'dateAccepted' => DcTerms::DATEACCEPTED,
      'dateDeleted' => OpenSkos::DATE_DELETED,
      
      'creator' => DcTerms::CREATOR,
      'contributor' => DcTerms::CONTRIBUTOR,
      'acceptedBy' => OpenSkos::ACCEPTEDBY,
      'deletedBy' => OpenSkos::DELETEDBY,
      
      'vcard_adr' => vCard::ADR,
      'vcard_org' => vCard::ORG,
      'vcard_orgname' => vCard::ORGNAME,
      'vcard_country'=>vCard::COUNTRY,
      'vcard_email'=> vCard::EMAIL,
      'vcard_locality'=>vCard::LOCALITY,
      'vcard_orgunit'=>vCard::ORGUNIT,
      'vcard_pcode'=>vCard::PCODE,
      'vcard_street'=>vCard::STREET,
      'vcard_url'=>vCard::URL,
      
      'rdf_type' => Rdf::TYPE,
      ];
  }

}
