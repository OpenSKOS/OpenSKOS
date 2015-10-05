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
        
        $solr2sparql = new Query\Solr2Sparql($this->request);
                
        $params = $this->request->getQueryParams();
        $start = 0;
        if (!empty($params['start'])) {
            $start = (int)$params['start'];
        }
        
        $count = $solr2sparql->getCount();
        $query = $solr2sparql->getSelect($this->limit, $start);

        $concepts = $this->manager->fetchQuery($query);

        $countResult = $this->manager->query($count);
        $total = $countResult[0]->count->getValue();

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
     * Get PSR-7 response for concept
     *
     * @param string $context
     * @throws Exception\NotFoundException
     * @throws Exception\InvalidArgumentException
     */
    public function getConcept($uuid, $context)
    {
        $prefixes = [
            'openskos' => \OpenSkos2\Namespaces\OpenSkos::NAME_SPACE,
        ];

        $lit = new \OpenSkos2\Rdf\Literal($uuid);
        $qb = new \Asparagus\QueryBuilder($prefixes);
        $query = $qb->describe('?subject')
            ->where('?subject', 'openskos:uuid', (new \OpenSkos2\Rdf\Serializer\NTriple)->serialize($lit));
        $data = $this->manager->fetchQuery($query);

        if (!count($data)) {
            throw new Exception\NotFoundException('Concept not found by id: ' . $uuid, 404);
        }
        
        /* @var $concept \OpenSkos2\Concept */
        $concept = $data[0];
        if ($concept->isDeleted()) {
            throw new Exception\DeletedException('Concept ' . $uuid . ' is deleted', 410);
        }
        
        switch ($context) {
            case 'json':
                $response = (new \OpenSkos2\Api\Response\Detail\JsonResponse($concept))->getResponse();
                break;
            case 'rdf':
                $response = (new \OpenSkos2\Api\Response\Detail\RdfResponse($concept))->getResponse();
                break;
            default:
                throw new Exception\InvalidArgumentException('Invalid context: ' . $context);
        }

        return $response;
    }
}
