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
     * @return \OpenSkos2\Concept[]
     */
    public function getConcepts($limit = 10, $offset = null)
    {
        $query = 'PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
            PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
            PREFIX dc: <http://purl.org/dc/elements/1.1/>
            PREFIX dct: <http://purl.org/dc/terms/>
            PREFIX openskos: <http://openskos.org/xmlns#>

            DESCRIBE ?subject ?predicate ?object
            WHERE {
              ?subject rdf:type skos:Concept
            }';
        
        if ($limit) {
            $query .= PHP_EOL. ' LIMIT ' . $limit;
        }
        
        if ($offset) {
            $query .= PHP_EOL. ' LIMIT ' . $offset;
        }
        
        return $this->fetchQuery($query);
    }
}
