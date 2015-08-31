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
namespace OpenSkos2;

use OpenSkos2\Rdf\Resource;
use OpenSkos2\Rdf\Uri;

class Concept extends Resource
{
    const TYPE = 'http://www.w3.org/2004/02/skos/core#Concept';

    //ConceptSchemes
    const PROPERTY_CONCEPTSCHEME = 'http://www.w3.org/2004/02/skos/core#conceptScheme';
    const PROPERTY_INSCHEME = 'http://www.w3.org/2004/02/skos/core#inScheme';
    const PROPERTY_HASTOPCONCEPT = 'http://www.w3.org/2004/02/skos/core#hasTopConcept';
    const PROPERTY_TOPCONCEPTOF = 'http://www.w3.org/2004/02/skos/core#topConceptOf';

    //LexicalLabels
    const PROPERTY_ALTLABEL = 'http://www.w3.org/2004/02/skos/core#altLabel';
    const PROPERTY_HIDDENLABEL = 'http://www.w3.org/2004/02/skos/core#hiddenLabel';
    const PROPERTY_PREFLABEL = 'http://www.w3.org/2004/02/skos/core#prefLabel';

    //Notations
    const PROPERTY_NOTATION = 'http://www.w3.org/2004/02/skos/core#notation';

    //DocumentationProperties
    const PROPERTY_CHANGENOTE = 'http://www.w3.org/2004/02/skos/core#changeNote';
    const PROPERTY_DEFINITION = 'http://www.w3.org/2004/02/skos/core#definition';
    const PROPERTY_EDITORIALNOTE = 'http://www.w3.org/2004/02/skos/core#editorialNote';
    const PROPERTY_EXAMPLE = 'http://www.w3.org/2004/02/skos/core#example';
    const PROPERTY_HISTORYNOTE = 'http://www.w3.org/2004/02/skos/core#historyNote';
    const PROPERTY_NOTE = 'http://www.w3.org/2004/02/skos/core#note';
    const PROPERTY_SCOPENOTE = 'http://www.w3.org/2004/02/skos/core#scopeNote';

    //SemanticRelations
    const PROPERTY_BROADER = 'http://www.w3.org/2004/02/skos/core#broader';
    const PROPERTY_BROADERTRANSITIVE = 'http://www.w3.org/2004/02/skos/core#broaderTransitive';
    const PROPERTY_NARROWER = 'http://www.w3.org/2004/02/skos/core#narrower';
    const PROPERTY_NARROWERTRANSITIVE = 'http://www.w3.org/2004/02/skos/core#narrowerTransitive';
    const PROPERTY_RELATED = 'http://www.w3.org/2004/02/skos/core#related';
    const PROPERTY_SEMANTICRELATION = 'http://www.w3.org/2004/02/skos/core#semanticRelation';

    //ConceptCollections
    const PROPERTY_COLLECTION = 'http://www.w3.org/2004/02/skos/core#Collection';
    const PROPERTY_ORDEREDCOLLECTION = 'http://www.w3.org/2004/02/skos/core#OrderedCollection';
    const PROPERTY_MEMBER = 'http://www.w3.org/2004/02/skos/core#member';
    const PROPERTY_MEMBERLIST = 'http://www.w3.org/2004/02/skos/core#memberList';

    //MappingProperties
    const PROPERTY_BROADMATCH = 'http://www.w3.org/2004/02/skos/core#broadMatch';
    const PROPERTY_CLOSEMATCH = 'http://www.w3.org/2004/02/skos/core#closeMatch';
    const PROPERTY_EXACTMATCH = 'http://www.w3.org/2004/02/skos/core#exactMatch';
    const PROPERTY_MAPPINGRELATION = 'http://www.w3.org/2004/02/skos/core#mappingRelation';
    const PROPERTY_NARROWMATCH = 'http://www.w3.org/2004/02/skos/core#narrowMatch';
    const PROPERTY_RELATEDMATCH = 'http://www.w3.org/2004/02/skos/core#relatedMatch';

    //OpenSKOS specific
    const PROPERTY_OPENSKOS_STATUS = 'http://openskos.org/xmlns#status';
    const PROPERTY_OPENSKOS_TOBECHECKED = 'http://openskos.org/xmlns#toBeChecked';

    //Dcterms
    const PROPERTY_DCTERMS_DATESUBMITTED = 'http://purl.org/dc/terms/dateSubmitted';
    const PROPERTY_DCTERMS_DATEACCEPTED = 'http://purl.org/dc/terms/dateAccepted';
    const PROPERTY_DCTERMS_MODIFIED = 'http://purl.org/dc/terms/modified';
    const PROPERTY_DCTERMS_CREATOR = 'http://purl.org/dc/elements/1.1/creator';

    public static $classes = array(
        'ConceptSchemes' => [
            self::PROPERTY_CONCEPTSCHEME,
            self::PROPERTY_INSCHEME,
            self::PROPERTY_HASTOPCONCEPT,
            self::PROPERTY_TOPCONCEPTOF,
        ],
        'LexicalLabels' => [
            self::PROPERTY_ALTLABEL,
            self::PROPERTY_HIDDENLABEL,
            self::PROPERTY_PREFLABEL,
        ],
        'Notations' => [
            self::PROPERTY_NOTATION,
        ],
        'DocumentationProperties' => [
            self::PROPERTY_CHANGENOTE,
            self::PROPERTY_DEFINITION,
            self::PROPERTY_EDITORIALNOTE,
            self::PROPERTY_EXAMPLE,
            self::PROPERTY_HISTORYNOTE,
            self::PROPERTY_NOTE,
            self::PROPERTY_SCOPENOTE,
        ],
        'SemanticRelations' => [
            self::PROPERTY_BROADER,
            self::PROPERTY_BROADERTRANSITIVE,
            self::PROPERTY_NARROWER,
            self::PROPERTY_NARROWERTRANSITIVE,
            self::PROPERTY_RELATED,
            self::PROPERTY_SEMANTICRELATION,
        ],
        'ConceptCollections' => [
            self::PROPERTY_COLLECTION,
            self::PROPERTY_ORDEREDCOLLECTION,
            self::PROPERTY_MEMBER,
            self::PROPERTY_MEMBERLIST,
        ],
        'MappingProperties' => [
            self::PROPERTY_BROADMATCH,
            self::PROPERTY_CLOSEMATCH,
            self::PROPERTY_EXACTMATCH,
            self::PROPERTY_MAPPINGRELATION,
            self::PROPERTY_NARROWMATCH,
            self::PROPERTY_RELATEDMATCH,
        ],
//        'Dcterms
//            self::PROPERTY_DCTERMS_DATESUBMITTED,
//            self::PROPERTY_DCTERMS_DATEACCEPTED,
//            self::PROPERTY_DCTERMS_MODIFIED,
//            self::PROPERTY_DCTERMS_CREATOR,
//        ],
    );

    /**
     * Resource constructor.
     * @param string $uri
     */
    public function __construct($uri = null)
    {
        parent::__construct($uri);
        $this->addProperty(self::PROPERTY_RDF_TYPE, new Uri(self::TYPE));
    }


}