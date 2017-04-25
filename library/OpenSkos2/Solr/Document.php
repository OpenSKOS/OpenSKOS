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
use Solarium\QueryType\Update\Query\Document\DocumentInterface;

/**
 * Get a solr document from a skos concept resource
 */
// Meertens:
// -- removed OpenSkos::MODIFIEDBY, Dc::CONTRIBUTOR, Dc::CREATOR.
// -- added  OpenSkos:INSKOSKCOLLECTION,OpenSkos:DELTEDBY,OpenSkos:DATEDELETED
// -- calls to getOldField are removed, since migration script contains translation of old fields
// -- The Picturae's changes starting from 28/10/2016 are taken
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
        Skos::PREFLABEL => ['s_prefLabel', 't_prefLabel', 'a_prefLabel', 'sort_s_prefLabel'],
        Skos::ALTLABEL => ['s_altLabel', 't_altLabel', 'a_altLabel', 'sort_s_altLabel'],
        Skos::HIDDENLABEL => ['s_hiddenLabel', 't_hiddenLabel', 'a_hiddenLabel', 'sort_s_hiddenLabel'],
        Skos::DEFINITION => ['t_definition', 'a_definition', 'definition'],
        Skos::EXAMPLE => ['t_example', 'a_example'],
        Skos::CHANGENOTE => ['t_changeNote', 'a_changeNote'],
        Skos::EDITORIALNOTE => ['t_editorialNote', 'a_editorialNote'],
        Skos::HISTORYNOTE => ['t_historyNote', 'a_historyNote'],
        Skos::SCOPENOTE => ['t_scopeNote', 'a_scopeNote'],
        Skos::NOTATION => ['s_notation', 't_notation', 'a_notation'],
        Skos::INSCHEME => ['s_inScheme', 'inScheme'],
        OpenSkos::INSKOSCOLLECTION => ['s_inSkosCollection', 'inSkosCollection'],
        OpenSkos::STATUS => ['s_status'],
        OpenSkos::SET => ['s_set'],
        OpenSkos::TENANT => ['s_tenant'],
        OpenSkos::TOBECHECKED => ['b_toBeChecked'],
        DcTerms::CREATOR => ['s_creator'],
        DcTerms::DATESUBMITTED => ['d_dateSubmited'],
        DcTerms::CONTRIBUTOR => ['s_contributor'],
        DcTerms::MODIFIED => ['d_modified', 'sort_d_modified_earliest'],
        OpenSkos::ACCEPTEDBY => ['s_acceptedBy'],
        DcTerms::DATEACCEPTED => ['d_dateAccepted'],
        OpenSkos::DELETEDBY => ['s_deletedBy'],
        OpenSkos::DATE_DELETED => ['d_dateDeleted']
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

        // Index bare SKOS and OpenSKOs fields via their standart map to SKOS and OpenSKOS predicates
        $predicateToField = array_flip(FieldsMaps::getNamesToProperties());

        // Dc terms
        $dcTerms = DcTerms::getAllTerms();

        foreach ($properties as $predicate => $values) {
            if (!array_key_exists($predicate, $this->mapping)) {
                continue;
            }

            // Explicitly mapped fields
            $fields = $this->mapping[$predicate];

            // bare (non-"_"-prefixed) fields
            if (isset($predicateToField[$predicate])) {
                $fields[] = $predicateToField[$predicate];
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

            $this->addMaxNumericNotation();
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
            foreach (Resource::$classes[$propertiesClass] as $predicate) {
                if ($concept->hasProperty($predicate)) {
                    $values = array_merge($values, $concept->getProperty($predicate));
                }
            }
            $this->mapValuesToField($propertiesClass, $values, $document);
        }
    }

    /**
     * Add the special single numeric notation. Only used for get max notation of all concepts later.
     */
    protected function addMaxNumericNotation()
    {
        // Gets one of the numeric notations of the concept.
        // Should be the highest one.

        $max = 0;
        foreach ($this->resource->getProperty(Skos::NOTATION) as $notation) {
            $value = $notation->getValue();
            if (is_numeric($value) && $value > $max) {
                $max = $value;
            }
        }

        if (!empty($max)) {
            $this->document->max_numeric_notation = $max;
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
            // Make sure we have valid dates.
            if (strpos($field, 'd_') === 0 && $value->getType() !== Literal::TYPE_DATETIME) {
                continue;
            }

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

        // Filter the first modified date
        if ($field === 'sort_d_modified_earliest') {
            usort($data[$field], function ($a, $b) {
                return (new \DateTime($a))->getTimestamp() > (new \DateTime($b))->getTimestamp();
            });
            $data[$field] = [current($data[$field])];
        }

        // Flat array for sorting
        if ($this->isSortField($field)) {
            array_walk($data, function (&$mappedValues) {
                sort($mappedValues);
                $mappedValues = implode('; ', $mappedValues);
            });
        }

        foreach ($data as $mappedField => $val) {
            if ($mappedField === 'notation') {
                $val = $this->getLiteral($val);
            }

            $document->{$mappedField} = $val;
        }
    }

    /**
     *
     * @param array $val
     * @return string|int
     * @throws Exception\InvalidValue
     */
    private function getLiteral(array $val)
    {
        if (count($val) > 1) {
            throw new Exception\InvalidValue('Invalid value for notation: ' . var_export($val, true));
        }

        return current($val);
    }

    /**
     * Is the mapping field a sort field.
     * @param string $field
     * @return bool
     */
    protected function isSortField($field)
    {
        return stripos($field, 'sort_') === 0;
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
                    return (bool) $value->getValue();
                default:
                    return $value->getValue();
            }
        }
    }
}
