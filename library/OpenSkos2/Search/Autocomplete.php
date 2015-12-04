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
use OpenSkos2\FieldsMaps;

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
     * @return ConceptCollection
     */
    public function search($options, &$numFound)
    {
        // @TODO Created, modified, approved section not implemented yet.
        // @TODO Ensure all options are arrays.
        
        $helper = new \Solarium\Core\Query\Helper();
        
        $parser = new ParserText();
        
        $term = $options['searchText'];
        
        $isDirectQuery = !empty($options['directQuery']);
        
        $solrQuery = '';
        
        
        if ($isDirectQuery) {
            
            
            $term = '(' . $term . ')';
            $solrQuery = $term;
            
            
        } else {
            // Fields
            
            
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

        
        
        
        
        
        
        
        
        
        
        //status
        if (!empty($options['status'])) {
            $solrQuery .= ' AND (';
            $solrQuery .= 's_status:'
                . implode(' OR ', array_map([$helper, 'escapePhrase'], $options['status']))
                . ')';
        } else {
            $solrQuery .= ' AND (-s_status:' . Resource::STATUS_DELETED . ')';
        }
        
        // sets (collections)
        if (!empty($options['collections'])) {
            $solrQuery .= ' AND (';
            $solrQuery .= 's_set:'
                . implode(' OR ', array_map([$helper, 'escapePhrase'], $options['collections']))
                . ')';
        }

        // schemes
        if (!empty($options['conceptScheme'])) {
            $solrQuery .= ' AND (';
            $solrQuery .= 's_inScheme:'
                . implode(' OR ', array_map([$helper, 'escapePhrase'], $options['conceptScheme']))
                . ')';
        }
        
        // tenants
        if (!empty($options['tenants'])) {
            $solrQuery .= ' AND (';
            $solrQuery .= 's_tenant:'
                . implode(' OR ', array_map([$helper, 'escapePhrase'], $options['tenants']))
                . ')';
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
        
        
        echo $solrQuery; exit;
        
        return $this->manager->search($solrQuery, $options['rows'], $options['start'], $numFound);
    }
}
