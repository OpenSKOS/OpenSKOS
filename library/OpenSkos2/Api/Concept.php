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

namespace OpenSkos2\Api;

/**
 * Map an API request from the old application to still work with the new backend on Jena
 */
class Concept
{

    const QUERY_DESCRIBE = 'describe';
    const QUERY_COUNT = 'count';

    /**
     * Concept manager
     *
     * @var \OpenSkos2\ConceptManager
     */
    private $manager;

    /**
     * @var \Psr\Http\Message\ServerRequestInterface
     */
    private $request;

    /**
     * Amount of concepts to return
     *
     * @var int
     */
    private $limit = 20;

    /**
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     */
    public function __construct(\OpenSkos2\ConceptManager $manager, \Psr\Http\Message\ServerRequestInterface $request)
    {
        $this->request = $request;
        $this->manager = $manager;
    }

    /**
     * Map the following requests
     *
     * /api/find-concepts?q=Kim%20Holland
     * /api/find-concepts?&fl=prefLabel,scopeNote&format=json&q=inScheme:"http://uri"
     * /api/find-concepts?format=json&fl=uuid,uri,prefLabel,class,dc_title&id=http://data.beeldengeluid.nl/gtaa/27140
     * /api/concept/82c2614c-3859-ed11-4e55-e993c06fd9fe.rdf
     *
     * @param string $context
     * @return ConceptResultSet
     */
    public function findConcepts($context)
    {
        $count = $this->getConceptFinderQuery(self::QUERY_COUNT);
        $query = $this->getConceptFinderQuery(self::QUERY_DESCRIBE);

        $concepts = $this->manager->fetchQuery($query);

        $countResult = $this->manager->query($count);
        $total = $countResult[0]->count->getValue();

        $params = $this->request->getQueryParams();

        $start = 0;
        if (!empty($params['start'])) {
            $start = (int)$params['start'];
        }

        $result = new ConceptResultSet($concepts, $total, $start);
        
        switch ($context) {
            case 'json':
                $response = (new \OpenSkos2\Api\Response\ResultSet\JsonResponse($result))->getResponse();
                break;
            case 'rdf':
                $response = (new \OpenSkos2\Api\Response\ResultSet\RdfResponse($result))->getResponse();
                break;
            default:
                throw new Exception\InvalidArgumentException('Invalid context: ' . $context);
        }

        return $response;
    }

    /**
     * Get describe or count query
     *
     * @param string $type
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

        $query->where('?subject', 'rdf:type', 'skos:Concept')
            ->limit($this->limit);

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
