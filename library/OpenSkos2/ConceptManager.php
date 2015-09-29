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
     *
     * @param int $limit
     * @param int $offset
     * @param \DateTime $from
     * @param \DateTime $till
     * @return \OpenSkos2\Concept[]
     */
    public function getConcepts($limit = 10, $offset = 0, \DateTime $from = null, \DateTime $till = null)
    {
        $prefixes = [
            'rdf' => Namespaces\Rdf::NAME_SPACE,
            'skos' => Namespaces\Skos::NAME_SPACE,
            'dc' => Namespaces\Dc::NAME_SPACE,
            'dct' => Namespaces\DcTerms::NAME_SPACE,
            'openskos' => Namespaces\OpenSkos::NAME_SPACE,
            'xsd' => Namespaces\Xsd::NAME_SPACE
        ];

        $qb = new \Asparagus\QueryBuilder($prefixes);
        $select = $qb->describe('?subject')
                ->where('?subject', 'rdf:type', 'skos:Concept')
                ->also('dct:modified', '?modified')
                ->limit($limit)
                ->offset($offset);

        if (!empty($from)) {
            $tFrom = $from->format(DATE_W3C);
            $select->filter('?modified >= "' . $tFrom . '"^^xsd:dateTime');
        }

        if (!empty($till)) {
            $tTill = $till->format(DATE_W3C);
            $select->filter('?modified >= "' . $tTill . '"^^xsd:dateTime');
        }

        $sparql = $select->getSPARQL();
        
        return $this->fetchQuery($sparql);
    }
}
