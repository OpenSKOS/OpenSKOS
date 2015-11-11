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

namespace OpenSkos2\OaiPmh;

use DOMDocument;
use OpenSkos2\Concept as SkosConcept;
use Picturae\OaiPmh\Implementation\Record\Header;
use Picturae\OaiPmh\Interfaces\Record;
use OpenSkos2\Api\Transform\DataRdf;

class Concept implements Record
{
    /**
     * @var SkosConcept $concept
     */
    private $concept;
    
    /**
     *
     * @param SkosConcept $concept
     */
    public function __construct(SkosConcept $concept)
    {
        $this->concept = $concept;
    }
    
    /**
     * Get header
     * @return Header
     */
    public function getHeader()
    {
        $concept = $this->concept;
        $datestamp = $concept->getProperty(\OpenSkos2\Namespaces\DcTerms::MODIFIED)[0]->getValue();
        $setSpecs = [];
        
        $tenants = $concept->getProperty(\OpenSkos2\Namespaces\OpenSkos::TENANT);
        $sets = $concept->getProperty(\OpenSkos2\Namespaces\OpenSkos::SET);
        
        foreach ($tenants as $tenant) {
            $setSpecs[] = (string)$tenant;
        }
        
        foreach ($sets as $set) {
            $arrSet = explode('/', (string)$set);
            $cleanSet = end($arrSet);
            $setSpecs[] = $cleanSet;
        }

        return new Header($concept->geturi(), $datestamp, $setSpecs);
    }

    /**
     * Convert skos concept to \DomDocument to use as metadata in OAI-PMH Interface
     *
     * @return DOMDocument
     */
    public function getMetadata()
    {
        $metadata = new \DOMDocument();
        $metadata->loadXML(
            (new DataRdf($this->concept))->transform()
        );
        
        return $metadata;
    }
    
    /**
     * @return DomDocument|null
     */
    public function getAbout()
    {
    }
}
