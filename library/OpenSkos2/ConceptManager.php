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

use Asparagus\QueryBuilder;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Rdf\Literal;
use OpenSkos2\Rdf\ResourceManager;
use OpenSkos2\Rdf\Serializer\NTriple;

class ConceptManager extends ResourceManager
{

    /**
     * What is the basic resource for this manager.
     * @var string NULL means any resource.
     */
    protected $resourceType = Concept::TYPE;

    /**
     * Supported relation types to add or remove
     * @var array
     */
    private $relationTypes = [
        Skos::BROADER,
        Skos::NARROWER,
        Skos::BROADERTRANSITIVE,
        Skos::NARROWERTRANSITIVE,
        Skos::BROADMATCH,
        Skos::NARROWMATCH,
        Skos::RELATED,
        Skos::TOPCONCEPTOF,
        Skos::HASTOPCONCEPT,
    ];

    /**
     * Perform basic autocomplete search on pref and alt labels
     *
     * @param string $term
     * @return array
     */
    public function autoComplete($term)
    {
        $prefixes = [
            'skos' => Skos::NAME_SPACE,
            'openskos' => OpenSkos::NAME_SPACE
        ];

        $literalKey = new Literal('^' . $term);
        $eTerm = (new NTriple())->serialize($literalKey);

        $q = new QueryBuilder($prefixes);

        // Do a distinct query on pref and alt labels where string starts with $term
        $query = $q->selectDistinct('?label')
                ->union(
                    $q->newSubgraph()
                        ->where('?subject', 'openskos:status', '"' . Concept::STATUS_APPROVED . '"')
                        ->also('skos:prefLabel', '?label'),
                    $q->newSubgraph()
                        ->where('?subject', 'openskos:status', '"' . Concept::STATUS_APPROVED . '"')
                        ->also('skos:altLabel', '?label')
                )
                ->filter('regex(str(?label), ' . $eTerm . ', "i")')
                ->limit(50);

        $result = $this->query($query);

        $items = [];
        foreach ($result as $literal) {
            $items[] = $literal->label->getValue();
        }
        return $items;
    }

    /**
     * Add relations to a skos concept
     *
     * @param string $uri
     * @param string $relationType
     * @param array $uris
     * @throws Exception\InvalidArgumentException
     */
    public function addRelation($uri, $relationType, $uris)
    {
        if (!in_array($relationType, $this->relationTypes, true)) {
            throw new Exception\InvalidArgumentException('Relation type not supported: ' . $relationType);
        }

        $graph = new \EasyRdf\Graph();
        foreach ($uris as $related) {
            $graph->add($uri, $relationType, $related);
        }

        $this->client->insert($graph);
    }

    /**
     * Add relations to both sides of the concepts for example.
     * http://example.com/1 skos::narrower [http://example.com/2]
     *
     * Will add http://example.com/2 as narrower for http://example.com/1
     * and add http://example.com/1 as broader for http://example.com/1
     *
     * This will not update transitive relations
     *
     * @param string $uri
     * @param string $relationType
     * @param array $uris
     */
    public function addRelationBothsides($uri, $relationType, $uris)
    {
        $this->addRelation($uri, $relationType, $uris);
        
        $inverse = $this->getInverseRelation($relationType);
        foreach ($uris as $relation) {
            $graph = new \EasyRdf\Graph();
            $graph->add($relation, $inverse, $uri);
            $this->client->insert($graph);
        }
    }
    
    /**
     * Perform a full text query
     * lucene / solr queries are possible
     * for the available fields see schema.xml
     * 
     * @param string $query
     * @param int $rows
     * @param int $start
     */
    public function search($query, $rows = 20, $start = 0)
    {
        $select = $this->solr->createSelect();
        $select->setStart($start)
                ->setRows($rows)
                ->setFields(['uri'])
                ->setQuery($query);
        
        $result = $this->solr->select($select);

        $return = [
            'total' => $result->getNumFound(),
            'concepts' => [],
        ];
        
        foreach ($result as $document) {
            $return['concepts'][] = $document->getFields();
        }
        
        var_dump($return); exit;
    }

    /**
     * Get inverse of skos relation
     *
     * @param string $relation
     * @param string
     * @throws Exception\InvalidArgumentException
     */
    private function getInverseRelation($relation)
    {
        switch ($relation) {
            case Skos::BROADER:
                return Skos::NARROWER;
                break;
            case Skos::NARROWER:
                return Skos::BROADER;
                break;
            case Skos::RELATED:
                return Skos::RELATED;
                break;
            case Skos::BROADMATCH:
                return Skos::NARROWMATCH;
                break;
            case Skos::NARROWMATCH:
                return Skos::BROADMATCH;
                break;
            case Skos::BROADERTRANSITIVE:
                return Skos::NARROWERTRANSITIVE;
                break;
            case Skos::NARROWERTRANSITIVE:
                return Skos::BROADERTRANSITIVE;
                break;
            case Skos::TOPCONCEPTOF:
                return Skos::HASTOPCONCEPT;
                break;
            case Skos::HASTOPCONCEPT:
                return Skos::TOPCONCEPTOF;
                break;
            default:
                throw new Exception\InvalidArgumentException('Relation not supported');
                break;
        }
    }
}
