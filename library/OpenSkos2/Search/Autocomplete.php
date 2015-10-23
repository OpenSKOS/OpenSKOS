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

class Autocomplete
{

    /**
     * @var \OpenSkos2\ConceptManager
     */
    private $manager;

    /**
     * @param \OpenSkos2\ConceptManager $manager
     */
    public function __construct(\OpenSkos2\ConceptManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Perform a autocomplete search with a search profile from the editor
     *
     * @param array $options
     */
    public function search($options)
    {
        $helper = new \Solarium\Core\Query\Helper();

        $term = $helper->escapePhrase($options['searchText']);
        $languages = $options['languages'];

        $solrQuery = '';

        $solrQuery .= '(';
        // labels
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

        // notes
        foreach ($options['properties'] as $property) {
            foreach ($languages as $lang) {
                $solrQuery .= 't_'.$property.':'.$term.' OR ';
            }
        }

        // strip last or
        $solrQuery = substr($solrQuery, 0, -4);

        // search notation
        if ($options['searchNotation']) {
            $solrQuery .= ' OR s_notation:'.$term;
        }

        // search uri
        if ($options['searchUri']) {
            $solrQuery .= ' OR s_uri:'.$term;
        }


        $solrQuery .= ')';

        //status
        if (count($options['status'])) {
            $solrQuery .= ' AND (';
            foreach ($options['status'] as $status) {
                $solrQuery .= 's_status:"'.$status.'" OR ';
            }
            $solrQuery = substr($solrQuery, 0, -4);
            $solrQuery .= ')';
        }

        // topconcepts
        if ($options['topConcepts']) {
            $solrQuery .= ' AND (b_isTopConcept:true) ';
        }

        // orphaned concepts
        if ($options['orphanedConcepts']) {
            $solrQuery .= ' AND (b_isOrphan:true) ';
        }

        // tenants
        if (!empty($options['tenants'])) {
            $solrQuery .= ' AND (';
            foreach ($options['tenants'] as $tenant) {
                $solrQuery .= 's_tenant:' . $helper->escapePhrase($tenant) . ' OR ';
                $solrQuery = substr($solrQuery, 0, -4);
            }
            $solrQuery .= ')';
        }

        return $this->manager->search($solrQuery, $options['rows'], $options['start']);
    }
}
