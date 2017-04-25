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
use Solarium\Core\Query\Helper as QueryHelper;

// Meertens:
// - 'collection' is not used as key in our version, use 'set' and 'skosCollection' instead
// Picturae's changes starting from  28/10/2016 are taken.
// in search $boost variable is moved  up in the loop for labels, otherwise for label with no languages it is not defined.
//-- added new parameter 'wholeword' to handle switch betwen whole word search (prefix t_) and the part-of-word search (prefix a_)
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
     *
     * @return \OpenSkos2\ConceptCollection
     */
    public function search($options, &$numFound)
    {

        $helper = new QueryHelper();

        $parser = new ParserText();

        $searchText = $options['searchText'];

        // Empty query and query for all is replaced with *
        $searchText = trim($searchText);
        if (empty($searchText) || $searchText == '*:*') {
            $searchText = '*';
        }

        // In all other cases - start parsing the query
        if ($searchText != '*') {
            $searchText = $parser->replaceLanguageTags($searchText);

            if ($parser->isSearchTextQuery($searchText) || $parser->isFieldSearch($searchText)) {
                // Custom user query, he has to escape and do everything.
                $searchText = '(' . $searchText . ')';
            } else {
                if ($parser->isFullyQuoted($searchText)) {
                    $searchText = $searchText;
                } elseif ($parser->isWildcardSearch($searchText)) {
                    // do not escape wildcard search with the new tokenizer
                    // $searchText = $helper->escapePhrase($searchText);
                } else {
                    $searchText = $helper->escapePhrase($searchText);
                }
            }
        }

        $prefix = '';
        //Meertens: the feature wholeworld  works only  when labels and/or properties are given as request parameters
        if (isset($options['wholeword'])) {
            if ($options['wholeword']) {
                $prefix = 't_';
            }
        }

        // @TODO Better to use edismax qf
        $searchTextQueries = [];
        // labels
        if (!empty($options['label'])) {
            foreach ($options['label'] as $label) {
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

                if (!empty($options['languages'])) {
                    foreach ($options['languages'] as $lang) {
                        $searchTextQueries[] = $prefix . $label . '_' . $lang . ':' . $searchText . $boost;
                    }
                } else {
                    $searchTextQueries[] = $prefix . $label . ':' . $searchText . $boost;
                }
            }
        }

        // notes
        if (!empty($options['properties'])) {
            foreach ($options['properties'] as $property) {
                if (!empty($options['languages'])) {
                    foreach ($options['languages'] as $lang) {
                        $searchTextQueries[] = $prefix . $property . '_' . $lang . ':' . $searchText;
                    }
                } else {
                    $searchTextQueries[] = $prefix . $property . ':' . $searchText;
                }
            }
        }

        // search notation
        if (!empty($options['searchNotation'])) {
            $searchTextQueries[] = 's_notation:' . $searchText;
        }

        // search uri
        if (!empty($options['searchUri'])) {
            $searchTextQueries[] = 's_uri:' . $searchText;
        }



        if (empty($searchTextQueries)) {
            $solrQuery = $searchText;
        } else {
            $solrQuery = '(' . implode(' OR ', $searchTextQueries) . ')';
        }

        // @TODO Use filter queries
        $optionsQueries = [];

        //status
        if (strpos($searchText, 'status') === false) { // We dont add status query if it is in the query already.
            if (!empty($options['status'])) {
                $optionsQueries[] = '('
                    . 's_status:('
                    . implode(' OR ', array_map([$helper, 'escapePhrase'], $options['status']))
                    . '))';
            } else {
                $optionsQueries[] = '-s_status:' . Resource::STATUS_DELETED;
            }
        }

        // sets (former tenant collections)
        if (!empty($options['set'])) {
            $solrQuery .= ' AND (';
            $solrQuery .= 's_set:('
                . implode(' OR ', array_map([$helper, 'escapePhrase'], $options['set']))
                . '))';
        }

        // skos collections
        if (!empty($options['skosCollection'])) {
            $solrQuery .= ' AND (';
            $solrQuery .= 's_inSkosCollection:('
                . implode(' OR ', array_map([$helper, 'escapePhrase'], $options['skosCollection']))
                . '))';
        }

        // schemes
        if (!empty($options['scheme'])) {
            $optionsQueries[] = '('
                . 's_inScheme:('
                . implode(' OR ', array_map([$helper, 'escapePhrase'], $options['scheme']))
                . '))';
        }

        // tenants
        if (!empty($options['tenant'])) {
            $optionsQueries[] = '('
                . 's_tenant:('
                . implode(' OR ', array_map([$helper, 'escapePhrase'], $options['tenant']))
                . '))';
        }

        // to be checked
        if (!empty($options['toBeChecked'])) {
            $optionsQueries[] = '(b_toBeChecked:true) ';
        }

        // topconcepts
        if (!empty($options['topConcepts'])) {
            $optionsQueries[] = '(b_isTopConcept:true) ';
        }

        // orphaned concepts
        if (!empty($options['orphanedConcepts'])) {
            $optionsQueries[] = '(b_isOrphan:true) ';
        }

        // combine
        if (!empty($optionsQueries)) {
            $optionsQuery = implode(' AND ', $optionsQueries);
            if (empty($solrQuery)) {
                $solrQuery = $optionsQuery;
            } else {
                // a possible bug in solr version
                if (trim($optionsQuery) != '-s_status:deleted') {
                    $solrQuery .= ' AND (' . $optionsQuery . ')';
                } else {
                    $solrQuery .= ' AND -s_status:deleted';
                }
            }
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

        return $this->manager->search($solrQuery, $options['rows'], $options['start'], $numFound, $sorts);
    }

    /**
     * Creates the query for creator, modifier and accepted by in combination with date created and etc.
     * @param array $options
     * @param \Solarium\Core\Query\Helper $helper
     * @param ParserText $parser
     * @return string
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
            'deleted' => [
                's_deletedBy',
                'd_dateDeleted',
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
}
