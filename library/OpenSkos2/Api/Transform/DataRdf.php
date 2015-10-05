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

namespace OpenSkos2\Api\Transform;

/**
 * Transform \OpenSkos2\Concept to a RDF string.
 * Provide backwards compatability to the API output from OpenSKOS 1 as much as possible
 */
class DataRdf
{
    
    /**
     * @var \OpenSkos2\Concept
     */
    private $concept;
    
    /**
     * @param \OpenSkos2\Concept $concept
     */
    public function __construct(\OpenSkos2\Concept $concept)
    {
        $this->concept = $concept;
    }
    
    /**
     * Transform the concept to xml string
     *
     * @return string
     */
    public function transform()
    {
        $concept = \OpenSkos2\Bridge\EasyRdf::resourceToGraph($this->concept);
        return $concept->serialise('rdf');
    }
}
