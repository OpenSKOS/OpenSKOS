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
use OpenSkos2\Namespaces\Xsd;
use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Namespaces\Rdf;
use OpenSkos2\Rdf\Literal;
use OpenSkos2\Rdf\Uri;
use OpenSkos2\Rdf\Serializer\NTriple;
use OpenSkos2\Rdf\ResourceManager;
use OpenSkos2\Rdf\Resource;
use OpenSkos2\MyInstitutionModules\Relations;
use OpenSkos2\Api\Exception\ApiException;


require_once dirname(__FILE__) .'/config.inc.php';
require_once dirname(__FILE__) . '/../../tools/Logging.php';

class ConceptManager extends ResourceManager
{
    /**
     * What is the basic resource for this manager.
     * @var string NULL means any resource.
     */
    protected $resourceType = Concept::TYPE;

    // uses and overrides the parent's method
    public function findResourceById($id, $resourceType) {
        $concept = parent::findResourceById($id, $resourceType);
        if ($concept->isDeleted()) {
            throw new ApiException('Resource with id ' . $id . ' is deleted', 410);
        }
        return $concept;
    }
    
    

    /**
     * Deletes and then inserts the resourse.
     * For concepts also deletes all relations for which the concept is object.
     * @param Concept $concept
     */
    
    
    
    public function replaceAndCleanRelations(Concept $concept)
    {
        // @TODO Danger if one of the operations fail. Need transaction or something.
        // @TODO What to do with imports. When several concepts are imported at once.
        foreach ($this -> fetchRelationUris() as $relationType) {
            $this->deleteMatchingTriples('?subject', $relationType, $concept);
        }
        parent::replace($concept);
    }

    /**
     * Perform basic autocomplete search on pref and alt labels
     *
     * @param string $term
     * @return array
     */
    public function autoComplete($term, $searchLabel = Skos::PREFLABEL, $returnLabel = Skos::PREFLABEL, $lang = null)
    {
        $literalKey = new Literal('^' . $term);
        $eTerm = (new NTriple())->serialize($literalKey);

        $q = new QueryBuilder();

        // Do a distinct query on pref and alt labels where string starts with $term
        $query = $q->selectDistinct('?returnLabel')
            ->where('?subject', '<' . OpenSkos::STATUS . '>', '"' . Resource::STATUS_APPROVED . '"')
            ->also('<' . $returnLabel . '>', '?returnLabel')
            ->also('<' . $searchLabel . '>', '?searchLabel')
            ->limit(50);
        
        $filter = 'regex(str(?searchLabel), ' . $eTerm . ', "i")';
        if (!empty($lang)) {
            $filter .= ' && ';
            $filter .= 'lang(?returnLabel) = "' . $lang . '"';
        }
        $query->filter($filter);

        $result = $this->query($query);

        $items = [];
        $i=0;
        foreach ($result as $literal) {
            $items[$i] = $literal->returnLabel->getValue();
            $i++;
        }
        return $items;
    }

   
   
   
    /**
     * Checks if there is a concept with the same pref label.
     * @param string $prefLabel
     * @return bool
     */
    public function askForPrefLabel($prefLabel)
    {
        return $this->askForMatch([
            [
                'predicate' => Skos::PREFLABEL,
                'value' => new Literal($prefLabel),
                'ignoreLanguage' => true
            ]
        ]);
    }
    
    /**
     * Deletes all concepts inside a concept scheme.
     * @param \OpenSkos2\ConceptScheme $scheme
     * @param \OpenSkos2\Person $deletedBy
     */
    public function deleteSoftInScheme(ConceptScheme $scheme, Person $deletedBy)
    {
        $start = 0;
        $step = 100;
        do {
            $concepts = $this->fetch(
                [
                    Skos::INSCHEME => $scheme,
                ],
                $start,
                $step
            );
            
            foreach ($concepts as $concept) {
                $inSchemes = $concept->getProperty(Skos::INSCHEME);
                if (count($inSchemes) == 1) {
                    $this->deleteSoft($concept, $deletedBy);
                } else {
                    $newSchemes = [];
                    foreach ($inSchemes as $inScheme) {
                        if (strcasecmp($inScheme->getUri(), $scheme->getUri()) !== 0) {
                            $newSchemes[] = $inScheme;
                        }
                    }
                    $concept->setProperties(Skos::INSCHEME, $newSchemes);
                    $this->replace($concept);
                }
            }
            $start += $step;
        } while (!(count($concepts) < $step));
    }
    
    /**
     * Perform a full text query
     * lucene / solr queries are possible
     * for the available fields see schema.xml
     *
     * @param string $query
     * @param int $rows
     * @param int $start
     * @param int &$numFound output Total number of found records.
     * @return ConceptCollection
     */
    public function search($query, $rows=MAXIMAL_ROWS, $start = 0, &$numFound=0, $sorts = null)
    {
        // @TODO There is nowhere in solr check for class:Concept, but all resources are there
        $solr_result = $this->solrResourceManager->search($query, $rows, $start, $numFound, $sorts);
        return $this->fetchByUris($solr_result);
    }
    
    /**
     * Gets the current max numeric notation for all concepts. Fast.
     * @param \OpenSkos2\Tenant $tenant
     * @return int|null
     */
    public function fetchMaxNumericNotationFromIndex(Tenant $tenant)
    {
        // Gets the maximum of all max_numeric_notation fields
        $max = $this->solrResourceManager->getMaxFieldValue(
            'tenant:' . $tenant->getCode(),
            'max_numeric_notation'
        );
        
        return intval($max);
    }
    
    /**
     * Gets the current max numeric notation.
     * This method is extremely slow...
     * @see self::fetchMaxNumericNotationFromIndex
     * @param \OpenSkos2\Tenant $tenant
     * @return int|null
     */
    public function fetchMaxNumericNotation(Tenant $tenant)
    {
        // This method is slow - use fetchMaxNumericNotationFromIndex where possible.
        $maxNotationQuery = (new QueryBuilder())
            ->select('(MAX(<' . Xsd::NONNEGATIVEINTEGER . '>(?notation)) AS ?maxNotation)')
            ->where('?subject', '<' . Skos::NOTATION . '>', '?notation')
            ->also('<' . OpenSkos::TENANT . '>', $this->valueToTurtle(new Literal($tenant->getCode())))
            ->filter('regex(?notation, \'^[0-9]*$\', "i")');
        
        $maxNotationResult = $this->query($maxNotationQuery);
        
        $maxNotation = null;
        if (!empty($maxNotationResult->offsetGet(0)->maxNotation)) {
            $maxNotation = $maxNotationResult->offsetGet(0)->maxNotation->getValue();
        }
        
        return $maxNotation;
    }
    
    /**
     * Gets the current min dcterms:modified date.
     * @return \DateTime|null
     */
    public function fetchMinModifiedDate()
    {
        $minDateQuery = (new QueryBuilder())
            ->select('(MIN(?date) AS ?minDate)')
            ->where('?subject', '<' . DcTerms::MODIFIED . '>', '?date')
            ->also('<' . Rdf::TYPE . '>', '<' . $this->resourceType . '>');

        $minDateResult = $this->query($minDateQuery);
        
        $minDate = null;
        if (!empty($minDateResult->offsetGet(0)->minDate)) {
            $minDate = $minDateResult->offsetGet(0)->minDate;
            if ($minDate instanceof \EasyRdf\Literal\DateTime) {
                $minDate = new \DateTime('@' . $minDate->format('U'));
            }
        }
        
        return $minDate;
    }
    
     /**
     * Delete relations between two skos concepts.
     * Deletes in both directions (narrower and broader for example).
     * @param string $subjectUri
     * @param string $relationType
     * @param string $objectUri
     * @throws Exception\InvalidArgumentException
     */
    public function deleteRelationTriple($subjectUri, $relationType, $objectUri)
    {
        
        $this->deleteMatchingTriples(
            new Uri($subjectUri),
            $relationType,
            new Uri($objectUri)
        );
        $inverses = array_merge(Skos::getInverseRelationsMap(), Relations::$inverses);
        $this->deleteMatchingTriples(
            new Uri($objectUri),
            $inverses[$relationType],
            new Uri($subjectUri)
        );
        
    }
  
        /**
     * Add relations to a skos concept
     *
     * @param string $uri
     * @param string $relationType
     * @param array|string $uris
     * @throws Exception\InvalidArgumentException
     */
    public function addRelationTriple($uri, $relationType, $uris)
    {
        // @TODO Add check everywhere we may need it.
        if (in_array($relationType, [Skos::BROADERTRANSITIVE, Skos::NARROWERTRANSITIVE])) {
            throw new Exception\InvalidArgumentException(
                'Relation type "' . $relationType . '" will be inferred. Not supported explicitly.'
            );
        }

        $graph = new \EasyRdf\Graph();
        
        if (!is_array($uris)) {
            $uris = [$uris];
        }
        foreach ($uris as $related) {
            $graph->addResource($uri, $relationType, $related);
        }
        
        $this->client->insert($graph);
    }
    
   // all concepts from transitive closure for $conceptsUri;
    private function getClosure($conceptUri, $relationUri)
    {
        $query = 'select ?trans where {<'. $conceptUri . '>  <' .$relationUri.'>+ ' . '  ?trans . }';
        $response = $this ->query($query);
        $retVal = array();
        $i=0;
        foreach ($response as $key => $value) {
            $retVal[$i] = $value -> trans -> getUri();
            $i++;
        }
        return $retVal;
    }
    
    
    
    
    
    // a relation is invalid if it (possibly with its inverse) creates transitive link of a concept or related concept to itself
    public function relationTripleCreatesCycle($conceptUri, $relatedConceptUri, $relationUri) {
        $closure = $this->getClosure($relatedConceptUri, $relationUri);
        $transitive = ($conceptUri === $relatedConceptUri || in_array($conceptUri, $closure));
        if ($transitive) {
            throw new ApiException('The triple creates transitive link of the source to itself, possibly via inverse relation.', 400);
        }
        // overkill??
        $inverses = array_merge(Skos::getInverseRelationsMap(), Relations::$inverses);
        if (array_key_exists($relationUri, $inverses)) {
            $inverseRelUri = $inverses[$relationUri];
            $inverseClosure = $this->getClosure($conceptUri, $inverseRelUri);
            $transitiveInverse = ($relatedConceptUri === $conceptUri || in_array($relatedConceptUri, $inverseClosure));
            if ($transitiveInverse) {
                throw new ApiException('The triple creates inverse transitive link of the target to itself', 400);
            }
            // consistency check: if we want to add aRb, then we must first check if a(-R)^+b, not to have mutually exclusive relations
            // b is not in the inverse closure of a, but this is checked above
        }
        return false;
    }
    
    public function relationTripleIsDuplicated($conceptUri, $relatedConceptUri, $relationUri) {
        $count = $this->countTriples('<' . $conceptUri . '>', '<' . $relationUri . '>', '<' . $relatedConceptUri . '>');
        if ($count > 0) {
            $relation[] = $conceptUri;
            $relation[] = $relationUri;
            $relation[] = $relatedConceptUri;
            \Tools\Logging::var_logger("Info: There was an attempt to duplicate a relation: ", $relation, '/app/data/info.log');
            return false;
        }

        $trans = Relations::$transitive;
        if (!isset($trans[$relationUri]) || $trans[$relationUri] == null) {
            $closure = $this->getClosure($conceptUri, $relationUri);
            if (in_array($relatedConceptUri, $closure)) {
                $relation[] = $conceptUri;
                $relation[] = $relationUri;
                $relation[] = $relatedConceptUri;
                \Tools\Logging::var_logger("Error: concepts have not been updated because of attempt to add a relation which is in the transitive closure: ", $relation, '/app/data/info.log');
                throw new ApiException('Concepts have not been updated because of attempt to add a relation which is in the transitive closure.', 400);
            }
        };
        return false;
    }
  
}
