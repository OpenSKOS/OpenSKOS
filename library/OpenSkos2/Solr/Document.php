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

namespace OpenSkos2\Solr;

use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Rdf\Object;
use OpenSkos2\Rdf\Uri;
use OpenSkos2\Rdf\Literal;
use OpenSkos2\Rdf\Resource;
use OpenSkos2\FieldsMaps;
use OpenSkos2\Concept;
use Solarium\QueryType\Update\Query\Document\DocumentInterface;

/**
 * Get a solr document from a skos concept resource
 */
class Document
{
    /**
     * @var Resource
     */
    protected $resource;

    /**
     * @var DocumentInterface
     */
    protected $document;

    /**
     * These namespaces will be indexed to solr, if the value contains a language
     * it will be added to the fieldname as.
     *
     * s_notation (never has an language)
     * s_prefLabel_nl
     * s_prefLabel_en
     *
     * @TODO We now have old fields mapping + this one. Don't need them both.
     * 
     * @var array
     */
    protected $mapping = [
        Skos::PREFLABEL => ['s_prefLabel', 't_prefLabel', 'a_prefLabel'],
        Skos::ALTLABEL => ['s_altLabel', 't_altLabel', 'a_altLabel'],
        Skos::HIDDENLABEL => ['s_hiddenLabel', 't_hiddenLabel', 'a_hiddenLabel'],
        Skos::DEFINITION => ['t_definition', 'a_definition'],
        Skos::EXAMPLE => ['t_example', 'a_example'],
        Skos::CHANGENOTE => ['t_changeNote', 'a_changeNote'],
        Skos::EDITORIALNOTE => ['t_editorialNote', 'a_editorialNote'],
        Skos::HISTORYNOTE => ['t_historyNote', 'a_historyNote'],
        Skos::SCOPENOTE =>  ['t_scopeNote', 'a_scopeNote'],
        Skos::NOTATION =>   ['s_notaton', 't_notaton', 'a_notaton'],
        Skos::INSCHEME =>   ['s_inScheme'],
        OpenSkos::STATUS => ['s_status'],
        OpenSkos::SET => ['s_set'],
        OpenSkos::TENANT => ['s_tenant'],
        OpenSkos::TOBECHECKED => ['b_toBeChecked'],
        DcTerms::CREATOR => ['s_creator'],
        DcTerms::DATESUBMITTED => ['d_dateSubmited'],
        DcTerms::CONTRIBUTOR => ['s_contributor'],
        DcTerms::MODIFIED => ['d_modified'],
        OpenSkos::ACCEPTEDBY => ['s_acceptedBy'],
        DcTerms::DATEACCEPTED => ['d_dateAccepted'],
    ];

    /**
     *
     * @param Resource $resource
     * @param DocumentInterface $document
     */
    public function __construct(Resource $resource, DocumentInterface $document)
    {
        $this->resource = $resource;
        $this->document = $document;
    }

    /**
     * Get solr document
     *
     * @return DocumentInterface
     */
    public function getDocument()
    {
        $this->document->uri = $this->resource->getUri();
        $properties = $this->resource->getProperties();
        
        // Index old fields as well for bacward compatibility.
        $predicatesToOldField = array_flip(FieldsMaps::getOldToProperties());
        
        // Dc terms
        $dcTerms = DcTerms::getAllTerms();
        
        foreach ($properties as $predicate => $values) {
            if (!array_key_exists($predicate, $this->mapping)) {
                continue;
            }

            // Explicitly mapped fields
            $fields = $this->mapping[$predicate];
            
            // Old fields
            if (isset($predicatesToOldField[$predicate])) {
                $fields[] = $predicatesToOldField[$predicate];
            }
            
            // Dc terms
            $dcTermKey = array_search($predicate, $dcTerms);
            if ($dcTermKey !== false) {
                $fields[] = 'dcterms_' . $dcTermKey;
            }
            
            foreach ($fields as $field) {
                $this->mapValuesToField($field, $values, $this->document);
            }
        }
        
        if ($this->resource instanceof Concept) {
            $this->addConceptClasses($this->resource, $this->document);
            $this->document->b_isTopConcept = !$this->resource->isPropertyEmpty(Skos::TOPCONCEPTOF);
            $this->document->b_isOrphan = $this->isOrphan();
        }
        
        return $this->document;
    }
    
    /**
     * Check if the concept is an orphan
     *
     * @return boolean
     */
    protected function isOrphan()
    {
        if ($this->resource->isPropertyEmpty(Skos::BROADER)) {
            return false;
        }
        if ($this->resource->isPropertyEmpty(Skos::NARROWER)) {
            return false;
        }
        if ($this->resource->isPropertyEmpty(Skos::BROADERTRANSITIVE)) {
            return false;
        }
        if ($this->resource->isPropertyEmpty(Skos::NARROWERTRANSITIVE)) {
            return false;
        }
        if ($this->resource->isPropertyEmpty(Skos::NARROWMATCH)) {
            return false;
        }
        if ($this->resource->isPropertyEmpty(Skos::BROADMATCH)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Add lexical labels and documentation properties combined fields to document.
     * @param Concept $concept
     * @param DocumentInterface $document
     */
    protected function addConceptClasses(Concept $concept, DocumentInterface $document)
    {
        foreach (['LexicalLabels', 'DocumentationProperties'] as $propertiesClass) {
            $values = [];
            foreach (Concept::$classes[$propertiesClass] as $predicate) {
                if ($concept->hasProperty($predicate)) {
                    $values = array_merge($values, $concept->getProperty($predicate));
                }
            }
            $this->mapValuesToField($propertiesClass, $values, $document);
        }
    }

    /**
     * map [new Literal('test', 'nl')] to s_field_nl => test
     *
     * @param string $field
     * @param array $values
     * @param DocumentInterface $document
     */
    protected function mapValuesToField($field, array $values, DocumentInterface $document)
    {
        $langSeparator = '_';
        
        $data = [];
        foreach ($values as $value) {
            if (!isset($data[$field])) {
                $data[$field] = [];
            }
            $data[$field][] = $this->valueToSolr($value);
            
            // + language
            if (method_exists($value, 'getLanguage') && $value->getLanguage()) {
                $langField = $field . $langSeparator . $value->getLanguage();
                
                if (!isset($data[$langField])) {
                    $data[$langField] = [];
                }
                $data[$langField][] = $this->valueToSolr($value);
            }
        }

        foreach ($data as $field => $val) {
            $document->{$field} = $val;
        }
    }
    
    /**
     * @param Object $value
     * @return string
     */
    protected function valueToSolr(Object $value)
    {
        if ($value instanceof Uri) {
            return $value->getUri();
        } else {
            switch ($value->getType()) {
                case Literal::TYPE_DATETIME:
                    if ($value->getValue() instanceof \DateTime) {
                        return $value->getValue()->format('Y-m-d\TH:i:s.z\Z');
                    } else {
                        return gmdate('Y-m-d\TH:i:s.z\Z', strtotime($value->getValue()));
                    }
                    break;
                case Literal::TYPE_BOOL:
                    return (bool)$value->getValue();
                default:
                    return $value->getValue();
            }
        }
    }
}
