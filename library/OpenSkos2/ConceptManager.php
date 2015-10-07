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

use OpenSkos2\Rdf\ResourceManager;

class ConceptManager extends ResourceManager
{
    /**
     * What is the basic resource for this manager.
     * @var string NULL means any resource.
     */
    protected $resourceType = Concept::TYPE;

    /**
     * Perform basic autocomplete search on pref and alt labels
     *
     * @param string $term
     * @return array
     */
    public function autoComplete($term)
    {
        $prefixes = [
            'skos' => \OpenSkos2\Namespaces\Skos::NAME_SPACE,
            'openskos' => \OpenSkos2\Namespaces\OpenSkos::NAME_SPACE
        ];

        $literalKey = new \OpenSkos2\Rdf\Literal('^' . $term);
        $eTerm = (new \OpenSkos2\Rdf\Serializer\NTriple())->serialize($literalKey);

        $q = new \Asparagus\QueryBuilder($prefixes);

        // Do a distinct query on pref and alt labels where string starts with $term
        $query = $q->selectDistinct('?label')
            ->union(
                $q->newSubgraph()
                    ->where('?subject', 'openskos:status', '"'. \OpenSkos2\Concept::STATUS_APPROVED.'"')
                    ->also('skos:prefLabel', '?label'),
                $q->newSubgraph()
                    ->where('?subject', 'openskos:status', '"'. \OpenSkos2\Concept::STATUS_APPROVED.'"')
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
}
