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
use OpenSkos2\Rdf\ResourceManager;
use OpenSkos2\RelationManager;


require_once dirname(__FILE__) .'/config.inc.php';

class ConceptManager extends ResourceManager
{
    /**
     * What is the basic resource for this manager.
     * @var string NULL means any resource.
     */
    protected $resourceType = Concept::TYPE;

    /**
     * Deletes and then inserts the resourse.
     * For concepts also deletes all relations for which the concept is object.
     * @param Concept $concept
     */
    public function replaceAndCleanRelations(Concept $concept)
    {
        // @TODO Danger if one of the operations fail. Need transaction or something.
        // @TODO What to do with imports. When several concepts are imported at once.
        foreach (RelationManager::fetchUris() as $relationType) {
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
            ->where('?subject', '<' . OpenSkos::STATUS . '>', '"' . Concept::STATUS_APPROVED . '"')
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
        $select = $this->solr->createSelect();
        $select->setStart($start)
                ->setRows($rows)
                ->setFields(['uri'])
                ->setQuery($query);
        
        if (!empty($sorts)) {
            $select->setSorts($sorts);
        }
        
        
        $solrResult = $this->solr->select($select);
        $uris = [];
        foreach ($solrResult as $doc) {
            $uris[] = $doc->uri;
        }
        
        $retVal=$this->fetchByUris($uris);
        $numFound = count($retVal);
        return $retVal;
    }
    
    /**
     * Gets the current max numeric notation.
     * @param \OpenSkos2\Tenant $tenant
     * @return int|null
     */
    public function fetchMaxNumericNotation(Tenant $tenant)
    {
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
    
    
    
}
