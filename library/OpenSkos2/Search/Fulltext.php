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

class Fulltext
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
     * Perform a fulltext search with a search profile from the editor
     *
     * @param array $options
     */
    public function search($options)
    {
        $term = $options['searchText'];
        $languages = $options['languages'];

        $solrQuery = '';

        // search labels
        $solrQuery .= '(';
        foreach ($options['label'] as $label) {
            foreach ($languages as $lang) {
                $solrQuery .= 't_'.$label.'@'.$lang.':'.$term.' OR ';
            }
        }
        $solrQuery .= ')';
        
        // search status
        $solrQuery .= '(';
        foreach ($options['status'] as $status) {
            $solrQuery .= 's_status:"'.$status.'" OR ';
        }
        $solrQuery .= ')';
        
        return $this->manager->search($solrQuery);
    }
}
