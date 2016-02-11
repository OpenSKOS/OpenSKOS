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

namespace OpenSkos2\Search;

use OpenSkos2\Rdf\Resource;
use OpenSkos2\Namespaces\Skos as SkosNamespace;
use Solarium\Core\Query\Helper as QueryHelper;

require_once dirname(__FILE__) . '/../../../tools/Logging.php';
class Autocomplete
{
    /**
     * @var \OpenSkos2\ConceptManager
     */
    protected $manager;
    
    /**
     * @var OpenSKOS_Db_Table_Users
     */
    protected $usersModel;

    /**
     * @param \OpenSkos2\ConceptManager $manager
     * @param \OpenSKOS_Db_Table_Users $usersModel
     */
    public function __construct(\OpenSkos2\ConceptManager $manager, \OpenSKOS_Db_Table_Users $usersModel)
    {
        $this->manager = $manager;
        $this->usersModel = $usersModel;
    }

    /**
     * Perform a autocomplete search with a search profile from the editor
     *
     * @param array $options
     * @return ConceptCollection
     */
    public function search($options, &$numFound)
    {
        // @TODO Ensure all options are arrays.
        
        $helper = new QueryHelper();
        
        $parser = new ParserText();
        
        $term = $options['searchText'];
        
        $isDirectQuery = !empty($options['directQuery']);
        
        $solrQuery = '';
        
        if ($isDirectQuery) {
            if (!empty($term)) {
                $term = preg_replace('/([^@]*)@(\w{2}:)/', '$1_$2', $term); // Fix "@nl" to "_nl"
                // olha was here
                $term = $this -> prepareTextFields($term);
                // olha was here 
                if (trim($term) !== "*:*") { // somehow my solr fails on (*:*)
                    $term = '(' . $term . ')';
                }
                $solrQuery = $term;
            }
        } else {
            if ($parser->isSearchTextQuery($term)) {
                // Custom user query, he has to escape and do everything.
                $term = '(' . $term . ')';
            } else {
                $term = trim($term);
                if (empty($term)) {
                    $term = '*';
                }
                if (stripos($term, '*') !== false || stripos($term, '?') !== false) {
                    $term = $parser->escapeSpecialChars($term);
                } else {
                    $term = $helper->escapePhrase($term);
                }
            }
            
            
            $solrQuery .= '(';

            $languages = $options['languages'];

            // labels
            if (!empty($options['label'])) {
                foreach ($options['label'] as $label) {
                    foreach ($languages as $lang) {
                        // boost important labels
                        $boost = '';
                        if ($label === 'prefLabel') {
                            $boost = '^40';
                        }
                        if ($label === 'altLabel') {
                            $boost = '^20';
                        }
                        if ($label === 'hiddenLabel') {
                            $boost = '^10';
                        }

                        $solrQuery .= 'a_'.$label.'_'.$lang.':'.$term.$boost.' OR ';
                    }
                }
            }

            // notes
            if (!empty($options['properties'])) {
                foreach ($options['properties'] as $property) {
                    foreach ($languages as $lang) {
                        $solrQuery .= 't_'.$property.':'.$term.' OR ';
                    }
                }
            }

            // strip last or
            $solrQuery = substr($solrQuery, 0, -4);

            // search notation
            if (!empty($options['searchNotation'])) {
                $solrQuery .= ' OR s_notation:'.$term;
            }

            // search uri
            if (!empty($options['searchUri'])) {
                $solrQuery .= ' OR s_uri:'.$term;
            }

            $solrQuery .= ')';
        }

        if (!empty($solrQuery)) {
            $solrQuery .= ' AND ';
        }
        
        //status
        if (!empty($options['status'])) {
            $solrQuery .= ' (';
            $solrQuery .= 's_status:('
                . implode(' OR ', array_map([$helper, 'escapePhrase'], $options['status']))
                . '))';
        } else {
            $solrQuery .= ' (-s_status:' . Resource::STATUS_DELETED . ')';
        }
        
        // sets (collections)
        if (!empty($options['collections'])) {
            $solrQuery .= ' AND (';
            $solrQuery .= 's_set:('
                . implode(' OR ', array_map([$helper, 'escapePhrase'], $options['collections']))
                . '))';
        }

        // schemes
        if (!empty($options['conceptScheme'])) {
            $solrQuery .= ' AND (';
            $solrQuery .= 's_inScheme:('
                . implode(' OR ', array_map([$helper, 'escapePhrase'], $options['conceptScheme']))
                . '))';
        }
        
        // tenants
        if (!empty($options['tenants'])) {
            $solrQuery .= ' AND (';
            $solrQuery .= 's_tenant:('
                . implode(' OR ', array_map([$helper, 'escapePhrase'], $options['tenants']))
                . '))';
        }
        
        
        
        // to be checked
        if (!empty($options['toBeChecked'])) {
            $solrQuery .= ' AND (b_toBeChecked:true) ';
        }
        
        // topconcepts
        if (!empty($options['topConcepts'])) {
            $solrQuery .= ' AND (b_isTopConcept:true) ';
        }

        // orphaned concepts
        if (!empty($options['orphanedConcepts'])) {
            $solrQuery .= ' AND (b_isOrphan:true) ';
        }
        
        
        
        $interactionsQuery = $this->interactionsQuery($options, $helper, $parser);
        if (!empty($interactionsQuery)) {
            $solrQuery .= ' AND (' . $interactionsQuery . ')';
        }
        
        if (!empty($options['sorts'])) {
            $sorts = $options['sorts'];
        } else {
            $sorts = null;
        }
        
        $retVal = $this->manager->search($solrQuery, $options['rows'], $options['start'], $numFound, $sorts);
        //\Tools\Logging::var_error_log("\n solr query in searchAutocomplete ", $solrQuery, dirname(__FILE__) . '/../../../data/Logger.txt');
        //\Tools\Logging::var_error_log("\n Seacrh result in searchAutocomplete ", $retVal, dirname(__FILE__) . '/../../../data/Logger.txt');
        return $retVal;
    }
    
    /**
     * Creates the query for creator, modifier and accepted by in combination with date created and etc.
     * @param array $options
     * @param \Solarium\Core\Query\Helper $helper
     * @param ParserText $parser
     */
    protected function interactionsQuery($options, $helper, $parser)
    {
        $map = [
            'created' => [
                's_creator',
                'd_dateSubmited',
            ],
            'modified' => [
                's_contributor',
                'd_modified',
            ],
            'approved' => [
                's_acceptedBy',
                'd_dateAccepted',
            ],
        ];
        
        if (empty($options['userInteractionType'])) {
            $options['userInteractionType'] = [];
        }
        
        $interactionsQueries = [];
        foreach ($options['userInteractionType'] as $type) {
            $users = [];
            if (!empty($options['interactionByRoles'])) {
                $users = array_merge(
                    $users,
                    $this->usersModel->getUrisByRoles(
                        $options['tenants'],
                        $options['interactionByRoles']
                    )
                );
            }
            if (!empty($options['interactionByUsers'])) {
                $users = array_merge($users, $options['interactionByUsers']);
            }
            $users = array_unique($users);
            
            $dateQuery = $parser->buildDatePeriodQuery(
                $map[$type][1],
                isset($options['interactionDateFrom']) ? $options['interactionDateFrom'] : null,
                isset($options['interactionDateTo']) ? $options['interactionDateTo'] : null
            );
            
            $query = '';
            if (!empty($users)) {
                $query = $map[$type][0] . ':('
                    . implode(' OR ', array_map([$helper, 'escapePhrase'], $users))
                    . ')';
            }
            
            if (!empty($query) && !empty($dateQuery)) {
                $query = '(' . $query . ' AND ' . $dateQuery . ')';
            } elseif (empty($query) && !empty($dateQuery)) {
                $query = $dateQuery;
            }
            
            $interactionsQueries[] = $query;
        }
        
        return implode(' OR ', $interactionsQueries);
    }
    
    private function prepareTextFields($searchterm){
        $tokenizedFields = SkosNamespace::getTokenizedFields();
        $retVal = $searchterm;
        foreach ($tokenizedFields as $field){
            $old = $field . "Text:";
            $new ="t_".$field . ":";
           $retVal = str_replace($old, $new, $retVal); 
        }
        return $retVal;
    }
}
