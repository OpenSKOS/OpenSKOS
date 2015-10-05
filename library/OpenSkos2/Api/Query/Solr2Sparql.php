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
 * Returned results will differ
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
            $query->describe('?subject');
        }
        
        if ($type !== self::QUERY_COUNT) {
            $query->where('?subject', 'rdf:type', 'skos:Concept')
                ->limit($this->limit)
                ->offset($this->offset);
        }

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
        
        if (isset($params['q'])) {
            $literalKey = new \OpenSkos2\Rdf\Literal('^' . $params['q']);
            $term = (new \OpenSkos2\Rdf\Serializer\NTriple())->serialize($literalKey);

            $query->also('skos:prefLabel', '?pref')
                    ->also('skos:altLabel', '?alt')
                    ->filter('regex(str(?pref), ' . $term . ') || regex(str(?alt), ' . $term . ')');
        }
        return $query;
    }
}
