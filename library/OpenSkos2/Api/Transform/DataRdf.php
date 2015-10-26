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

use OpenSkos2\EasyRdf\Serialiser\RdfXml\OpenSkosAsDescriptions as EasyRdfOpenSkos;

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
     * @var bool
     */
    private $includeRdfHeader = true;
    
    /**
     * @param \OpenSkos2\Concept $concept
     */
    public function __construct(\OpenSkos2\Concept $concept, $includeRdfHeader = true)
    {
        $this->concept = $concept;
        $this->includeRdfHeader = $includeRdfHeader;
        
        // @TODO - put it somewhere globally
        \EasyRdf\Format::registerSerialiser(
            'rdfxml_openskos',
            '\OpenSkos2\EasyRdf\Serialiser\RdfXml\OpenSkosAsDescriptions'
        );
    }
    
    /**
     * Transform the concept to xml string
     *
     * @return string
     */
    public function transform()
    {
        $concept = \OpenSkos2\Bridge\EasyRdf::resourceToGraph($this->concept);
        return $concept->serialise(
            'rdfxml_openskos',
            [EasyRdfOpenSkos::OPTION_RENDER_ITEMS_ONLY => !$this->includeRdfHeader]
        );
    }
}
