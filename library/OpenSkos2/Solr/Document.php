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
use OpenSkos2\Rdf\Resource;
use Solarium\QueryType\Update\Query\Document\DocumentInterface;

/**
 * Get a solr document from a resource
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
     * s_prefLabel@nl
     * s_prefLabel@en
     *
     * @var array
     */
    private $mapping = [
        Skos::PREFLABEL => ['s_prefLabel', 't_prefLabel'],
        Skos::ALTLABEL => ['s_altLabel', 't_altLabel'],
        Skos::HIDDENLABEL => ['s_hiddenLabel', 't_hiddenLabel'],
        Skos::DEFINITION => ['t_definition'],
        Skos::EXAMPLE => ['t_example'],
        Skos::CHANGENOTE => ['t_changeNote'],
        Skos::EDITORIALNOTE => ['t_editorialNote'],
        Skos::HISTORYNOTE => ['t_historyNote'],
        Skos::SCOPENOTE => ['t_scopeNote'],
        Skos::NOTATION => ['t_notaton'],
        Openskos::STATUS => ['s_status'],
        Openskos::TENANT => ['s_tenant'],
        DcTerms::CREATOR => ['s_creator', 't_creator'],
        DcTerms::CONTRIBUTOR => ['s_contributor', 't_contributor'],
        DcTerms::CREATED => ['d_created'],
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

        return $this->document;
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

            $language = null;
            if (method_exists($value, 'getLanguage')) {
                $language = $value->getLanguage();
            }

            if ($language) {
                $newField .= '@' . $language;
            }

            if (!isset($data[$newField])) {
                $data[$newField] = [];
            }

            if (!method_exists($value, 'getLanguage')) {
                $data[$newField][] = $value->getUri();
            } else {
                $data[$newField][] = $value->getValue();
            }
        }

        foreach ($data as $field => $val) {
            $document->{$field} = $val;
        }
    }
}
