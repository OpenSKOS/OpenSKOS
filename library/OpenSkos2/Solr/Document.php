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
use OpenSkos2\Namespaces\Openskos;
use OpenSkos2\Rdf\Uri;
use OpenSkos2\Rdf\Literal;
use OpenSkos2\Rdf\Resource;
use Solarium\QueryType\Update\Query\Document\DocumentInterface;

/**
 * Get a solr document from a skos concept resource
 */
class Document
{
    /**
     * @var Resource
     */
    private $resource;

    /**
     * @var DocumentInterface
     */
    private $document;

    /**
     * These namespaces will be indexed to solr, if the value contains a language
     * it will be added to the fieldname as.
     *
     * s_notation (never has an language)
     * s_prefLabel_nl
     * s_prefLabel_en
     *
     * @var array
     */
    private $mapping = [
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
        Openskos::STATUS => ['s_status'],
        Openskos::TENANT => ['s_tenant'],
        DcTerms::CREATOR => ['s_creator', 't_creator', 'a_creator'],
        DcTerms::CONTRIBUTOR => ['s_contributor', 't_contributor', 'a_contributor'],
        DcTerms::DATEACCEPTED => ['d_created'],
        DcTerms::MODIFIED => ['d_modified'],
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
        foreach ($properties as $predicate => $values) {
            if (!array_key_exists($predicate, $this->mapping)) {
                continue;
            }

            $fields = $this->mapping[$predicate];
            
            foreach ($fields as $field) {
                $this->mapValuesToField($field, $values, $this->document);
            }
        }
        
        $this->document->b_isTopConcept = !$this->resource->isPropertyEmpty(Skos::TOPCONCEPTOF);
        $this->document->b_isOrphan = $this->isOrphan();
        
        return $this->document;
    }
    
    /**
     * Check if the concept is an orphan
     *
     * @return boolean
     */
    private function isOrphan()
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
     * map [new Literal('test', 'nl')] to s_field@nl => test
     *
     * @param string $field
     * @param array $values
     * @param DocumentInterface $document
     */
    private function mapValuesToField($field, array $values, DocumentInterface $document)
    {
        $data = [];
        foreach ($values as $value) {
            $newField = $field;

            if (method_exists($value, 'getLanguage') && $value->getLanguage()) {
                $newField .= '_' . $value->getLanguage();
            }

            if (!isset($data[$newField])) {
                $data[$newField] = [];
            }

            if ($value instanceof Uri) {
                $data[$newField][] = $value->getUri();
            } else {
                $data[$newField][] = $this->valueToSolr($value);
            }
        }

        foreach ($data as $field => $val) {
            $document->{$field} = $val;
        }
    }
    
    protected function valueToSolr($value)
    {
        switch ($value->getType()) {
            case Literal::TYPE_DATETIME:
                if ($value->getValue() instanceof \DateTime) {
                    return $value->getValue()->format('Y-m-d\TH:i:s.z\Z');
                } else {
                    return gmdate('Y-m-d\TH:i:s.z\Z', strtotime($value->getValue()));
                }
                break;
            case Literal::TYPE_BOOL:
                return boolval($value->getValue());
                break;
            default:
                return $value->getValue();
        }
    }
}
