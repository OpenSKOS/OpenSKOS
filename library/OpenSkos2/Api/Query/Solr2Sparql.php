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

namespace OpenSkos2\Api\Query;

/**
 * Provides a basic conversion to map previous solr queries to sparql, as fallback
 * for the API when switching the backend from SOLR to Jena
 * When it's not possible to map the query throw an exception to stop execution
 *
 * Returned results will differ because of the tokenizers / stemmers used in solr
 */
class Solr2Sparql
{

    const QUERY_DESCRIBE = 'describe';
    const QUERY_COUNT = 'count';

    /**
     * @var \Psr\Http\Message\ServerRequestInterface
     */
    private $request;

    /**
     * Limit query
     * @var type
     */
    private $limit = 20;

    /**
     * Offset
     * @var int
     */
    private $offset = 0;

    /**
     * Field mapping
     *
     * @var array
     */
    private $fieldMapping = [
        'status' => \OpenSkos2\Namespaces\OpenSkos::STATUS,
        'altLabel' => \OpenSkos2\Namespaces\Skos::ALTLABEL,
        'prefLabel' => \OpenSkos2\Namespaces\Skos::PREFLABEL,
        'notation' => \OpenSkos2\Namespaces\Skos::NOTATION,
        'scopeNote' => \OpenSkos2\Namespaces\Skos::SCOPENOTE
    ];

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     */
    public function __construct(\Psr\Http\Message\ServerRequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * Return the sparql to execute
     *
     * @return \Asparagus\QueryBuilder
     */
    public function getSelect($limit, $offset)
    {
        $this->limit = $limit;
        $this->offset = $offset;
        return $this->getConceptFinderQuery(self::QUERY_DESCRIBE);
    }

    /**
     * Return the count query to show the numfound for the API output
     *
     * @return \Asparagus\QueryBuilder
     */
    public function getCount()
    {
        return $this->getConceptFinderQuery(self::QUERY_COUNT);
    }

    /**
     * Get describe or count query
     *
     * @param string $type
     * @return \Asparagus\QueryBuilder
     */
    private function getConceptFinderQuery($type)
    {
        $prefixes = [
            'rdf' => \OpenSkos2\Namespaces\Rdf::NAME_SPACE,
            'skos' => \OpenSkos2\Namespaces\Skos::NAME_SPACE,
            'dc' => \OpenSkos2\Namespaces\Dc::NAME_SPACE,
            'dct' => \OpenSkos2\Namespaces\DcTerms::NAME_SPACE,
            'openskos' => \OpenSkos2\Namespaces\OpenSkos::NAME_SPACE,
            'xsd' => \OpenSkos2\Namespaces\Xsd::NAME_SPACE
        ];

        $query = new \Asparagus\QueryBuilder($prefixes);

        if ($type === self::QUERY_COUNT) {
            $query->select('(COUNT(*) AS ?count)');
        }

        if ($type === self::QUERY_DESCRIBE) {
            $query->describe('?subject')
                ->limit($this->limit)
                ->offset($this->offset);
        }
        
        $query->where('?subject', 'rdf:type', 'skos:Concept');
        
        
        $this->buildSearchQuery($query);

        return $query;
    }

    /**
     * Add search term parameters
     *
     * @param \Asparagus\QueryBuilder $query
     * @return \Asparagus\QueryBuilder
     */
    private function buildSearchQuery(\Asparagus\QueryBuilder $query)
    {
        $params = $this->request->getQueryParams();
        if (!isset($params['q'])) {
            return;
        }

        $q = $params['q'];

        // Search term does not contain specific fields d a search on alt / pref label
        $tQuery = $this->tokenizeQuery($q);

        if (!empty($tQuery['term'])) {
            $this->addGenericSearch($query, $tQuery['term']);
        }
        
        foreach ($tQuery['fields'] as $i => $data) {
            $this->addFieldSearch($query, $data, '?param'.$i);
        }
        
        return $query;
    }
    
    /**
     * Add specific field search
     *
     * @param \Asparagus\QueryBuilder $query
     * @param array $data
     * @param string $param
     */
    private function addFieldSearch(\Asparagus\QueryBuilder $query, $data, $param)
    {
        if ($data['wildcard']) {
            $value = new \OpenSkos2\Rdf\Literal('^' . $data['value']);
            $eValue = (new \OpenSkos2\Rdf\Serializer\NTriple())->serialize($value);
            
            $query->also('skos:prefLabel', '?pref');
            return $query->filter('regex(str(?pref), ' . $eValue . ', "i")');
        }
        
        $value = new \OpenSkos2\Rdf\Literal($data['value']);
        $eValue = (new \OpenSkos2\Rdf\Serializer\NTriple())->serialize($value);
        
        $uri = new \OpenSkos2\Rdf\Uri($data['field']);
        $eField = (new \OpenSkos2\Rdf\Serializer\NTriple())->serialize($uri);
        
        $query->also($eField, $param);

        $query->filter('str('.$param.') = '.$eValue);

    }

    /**
     * Add search term to alt and pref labels
     * and detect wildcard
     *
     * @param \Asparagus\QueryBuilder $query
     * @param string $q
     */
    private function addGenericSearch(\Asparagus\QueryBuilder $query, $q)
    {
        $literalKey = new \OpenSkos2\Rdf\Literal('^' . $q);
        $term = (new \OpenSkos2\Rdf\Serializer\NTriple())->serialize($literalKey);

        // Case insensitive wildcard for
        $query->also('skos:prefLabel', '?pref')
                ->also('skos:altLabel', '?alt')
                ->filter('regex(str(?pref), ' . $term . ', "i") || regex(str(?alt), ' . $term . ', "i")');
    }

    /**
     * Tokenize the query
     *
     * @return array
     */
    private function tokenizeQuery($query)
    {
        $arrQ = explode(' ', $query);

        $generic = '';

        $fields = [];

        foreach ($arrQ as $qPart) {
            $qPart = trim($qPart);

            if (empty($qPart)) {
                continue;
            }

            if ($this->isFieldStatement($qPart)) {
                $fields[] = $this->getFieldData($qPart);
            } else {
                $generic .= $qPart . ' ';
            }
        }

        $generic = trim($generic);

        return [
            // generic search term (search for alt / pref label)
            'term' => $generic,
            // specific search fields
            'fields' => $fields
        ];
    }

    /**
     *
     * @param type $queryPart
     * @return array Description
     */
    private function getFieldData($queryPart)
    {
        $val = $this->getFieldValue($queryPart);
        return [
            'field' => $this->getField($queryPart),
            'value' => $val,
            'wildcard' => $this->isWildcard($queryPart),
            'language' => $this->getLanguage($val),
        ];
    }

    /**
     * Get language
     *
     * @return string|null
     */
    private function getLanguage($value)
    {
        $arr = explode('@', $value);
        if (count($arr) < 2) {
            return;
        }
        return $arr[1];
    }

    /**
     * Check if the query part is a wildcard
     *
     * @param string $queryValue
     * @return boolean
     */
    private function isWildcard($queryValue)
    {
        $parts = explode(':', $queryValue);
        // Check if the query part is wildcard
        if (substr($parts[1], -1) === '*') {
            return true;
        }
        return false;
    }

    /**
     * Check if the query part is statement for an exact field e.g status:"something"
     *
     * @param string $queryPart
     * @return boolean
     */
    private function isFieldStatement($queryPart)
    {
        $parts = explode(':', $queryPart);
        if (count($parts) < 2) {
            return false;
        }
        
        $field = explode('@', $parts[0])[0];

        // Check if the field is supported
        if (!array_key_exists($field, $this->fieldMapping)) {
            return false;
        }

        return true;
    }

    /**
     * Get field to query for
     *
     * @param string $queryPart
     * @return string
     */
    private function getField($queryPart)
    {
        $parts = explode(':', $queryPart);
        $parts[0];
        
        $field = explode('@', $parts[0])[0];

        if (array_key_exists($field, $this->fieldMapping)) {
            return $this->fieldMapping[$field];
        }
    }

    /**
     * Get field value
     *
     * @param string $queryPart
     * @return string
     */
    private function getFieldValue($queryPart)
    {
        $parts = explode(':', $queryPart);
        $value = trim($parts[1], ' *');
        return $value;
    }
}
