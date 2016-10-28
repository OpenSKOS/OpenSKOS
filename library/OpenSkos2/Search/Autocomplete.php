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

        // @TODO Better to use edismax qf

        $searchTextQueries = [];

        // labels
        if (!empty($options['label'])) {
            foreach ($options['label'] as $label) {
                if (!empty($options['languages'])) {
                    foreach ($options['languages'] as $lang) {
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

                        $searchTextQueries[] = 'a_' . $label . '_' . $lang . ':' . $searchText . $boost;
                    }
                } else {
                    $searchTextQueries[] = 'a_' . $label . ':' . $searchText . $boost;
                }
            }
        }

        // notes
        if (!empty($options['properties'])) {
            foreach ($options['properties'] as $property) {
                if (!empty($options['languages'])) {
                    foreach ($options['languages'] as $lang) {
                        $searchTextQueries[] = 'a_' . $property . '_' . $lang . ':' . $searchText;
                    }
                } else {
                    $searchTextQueries[] = 'a_' . $property . ':' . $searchText;
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

        // sets (collections)
        if (!empty($options['collections'])) {
            $optionsQueries[] = '('
                . 's_set:('
                . implode(' OR ', array_map([$helper, 'escapePhrase'], $options['collections']))
                . '))';
        }

        // schemes
        if (!empty($options['conceptScheme'])) {
            $optionsQueries[] = '('
                . 's_inScheme:('
                . implode(' OR ', array_map([$helper, 'escapePhrase'], $options['conceptScheme']))
                . '))';
        }

        // tenants
        if (!empty($options['tenants'])) {
            $optionsQueries[] = '('
                . 's_tenant:('
                . implode(' OR ', array_map([$helper, 'escapePhrase'], $options['tenants']))
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
                $solrQuery .= ' AND (' . $optionsQuery . ')';
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
     */
    protected function interactionsQuery($options, $helper, $parser)
    {
        $map = [
            'created' => [
                's_creator',
                'd_dateSubmited',
            ],
            'modified' => [
                's_modifiedBy',
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
}
