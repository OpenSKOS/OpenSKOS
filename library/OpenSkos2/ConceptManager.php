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
use OpenSkos2\Rdf\Uri;
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
     * String to combine / explode for concat values from sparql
     *
     * @var string
     */
    private $concatSeperator = '^';

    /**
     * String to combine and explode group_concat values from sparql
     *
     * @var string
     */
    private $groupConcatSeperator = '|';

    /**
     * Field seperator, used to add field names in concatted groups e.g
     * title@@this is my title
     *
     * @var string
     */
    private $concatFieldSeperator = '@@';
    
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
     * Fetches all relations (can be a large number) for the given relation type.
     * @param string $uri
     * @param string $relationType Skos::BROADER for example.
     * @param string $conceptScheme , optional Specify if you want relations from single concept scheme only.
     * @return ConceptCollection
     */
    public function fetchRelations($uri, $relationType, $conceptScheme = null)
    {
        // @TODO It is possible that there are relations to uris, for which there is no a resource.
        
        $allRelations = new ConceptCollection([]);
        
        $patterns = [
            [new Uri($uri), $relationType, '?subject'],
        ];
        
        if (!empty($conceptScheme)) {
            $patterns[Skos::INSCHEME] = new Uri($conceptScheme);
        }
        
        $start = 0;
        $step = 100;
        do {
            $relations = $this->fetch($patterns, $start, $step);
            foreach ($relations as $relation) {
                $allRelations->append($relation);
            }
            $start += $step;
        } while (!(count($relations) < $step));
        
        return $allRelations;
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
        
        $solrResult = $this->solr->select($select);
        
        $conceptUri = [];
        
        foreach ($solrResult as $doc) {
            $conceptUri[] = $doc->uri;
        }
        
        $conceptUris = \OpenSkos2\Sparql\Escape::escapeUris($conceptUri);

        // Add asparagus BIND support see: https://github.com/Benestar/asparagus/issues/26
        $query = '
            PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
            PREFIX openskos: <http://openskos.org/xmlns#>
            PREFIX dcterms: <http://purl.org/dc/terms/>
            PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>

            SELECT ?prefLabel ?uuid ?uri ?status
                (group_concat (
                    distinct concat ("uri",
                        "'.$this->concatFieldSeperator.'",
                        str(?scheme),
                        "'.$this->concatSeperator.'",
                        "dcterms_title",
                        "'.$this->concatFieldSeperator.'",
                        ?schemeTitleb,
                        "'.$this->concatSeperator.'",
                        "uuid",
                        "'.$this->concatFieldSeperator.'",
                        ?schemeUuidb);separator = "'.$this->groupConcatSeperator.'") AS ?schemes)
            WHERE {
                    ?uri rdf:type skos:Concept ;
                            skos:prefLabel ?prefLabel ;
                            openskos:uuid ?uuid ;
                            openskos:status ?status ;
                            skos:inScheme ?scheme .
                    OPTIONAL {
                        ?uri skos:inScheme ?scheme .
                            ?scheme dcterms:title ?schemeTitle ;
                            openskos:uuid ?schemeUuid .
                    }
                    FILTER (?uri IN ('.$conceptUris.'))
              BIND ( IF (BOUND (?schemeUuid), ?schemeUuid, \'\')  as ?schemeUuidb)
              BIND ( IF (BOUND (?schemeTitle), ?schemeTitle, \'\')  as ?schemeTitleb)
            }
            GROUP BY ?prefLabel ?uuid ?uri ?status
            LIMIT 20';
        
        $searchResult = $this->query($query);
        $items = [];
        foreach ($searchResult as $literal) {
            $arrLit = (array)$literal;
            if (empty($arrLit)) {
                continue;
            }

            $concept = [
                'uri' => (string)$literal->uri,
                'uuid' => (string)$literal->uuid,
                'previewLabel' => (string)$literal->prefLabel,
                'status' => (string)$literal->status
            ];

            if (isset($literal->schemes)) {
                $schemes = $this->decodeConcat((string)$literal->schemes);
                $concept['schemes'] = $this->addIconToScheme($schemes);
            }

            if (isset($literal->scopeNotes)) {
                $concept['scopeNotes'] = explode('|', (string)$literal->scopeNotes);
            }

            $items[] = $concept;
        }
        
        $return = [
            'numFound' => $solrResult->getNumFound(),
            'concepts' => $items
        ];
        
        return $return;
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
    
    /**
     * Add icon path to schemes
     *
     * @param array $schemes
     * @return array
     */
    private function addIconToScheme($schemes)
    {
        foreach ($schemes as $i => $scheme) {
            $scheme['iconPath'] = ConceptScheme::buildIconPath($scheme['uuid']);
            $schemes[$i] = $scheme;
        }
        return $schemes;
    }

    /**
     * Decode a string that has concat and group_concat values
     *
     * @param string $value
     * @return array
     */
    private function decodeConcat($value)
    {
        $decoded = [];
        $groups = explode($this->groupConcatSeperator, $value);
        foreach ($groups as $group) {
            $values = explode($this->concatSeperator, $group);
            $obj = [];
            foreach ($values as $groupValue) {
                if (empty($groupValue)) {
                    continue;
                }
                $fieldAndValue = explode($this->concatFieldSeperator, $groupValue);
                $fieldName = $fieldAndValue[0];
                $obj[$fieldName] = $fieldAndValue[1];
            }
            $decoded[] = $obj;
        }
        return $decoded;
    }
}
