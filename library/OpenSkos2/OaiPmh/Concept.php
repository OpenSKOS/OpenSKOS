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
use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Concept as SkosConcept;
use Picturae\OaiPmh\Implementation\Record\Header;
use Picturae\OaiPmh\Interfaces\Record;
use OpenSkos2\Api\Transform\DataRdf;

class Concept implements Record
{
    /**
     * @var SkosConcept $concept
     */
    protected $concept;
    
    /**
     * @var SetsMap
     */
    protected $setsMap;
    
    /**
     * @param SkosConcept $concept
     * @param \OpenSkos2\OaiPmh\SetsMap $setsMap
     */
    public function __construct(SkosConcept $concept, SetsMap $setsMap)
    {
        $this->concept = $concept;
        $this->setsMap = $setsMap;
    }

    /**
     * Get header
     * @return Header
     */
    public function getHeader()
    {
        $concept = $this->concept;
        
        if (!$concept->isDeleted()) {
            $datestamp = $concept->getPropertySingleValue(DcTerms::MODIFIED)->getValue();
        } else {
            if ($concept->hasProperty(OpenSkos::DATE_DELETED)) {
                $datestamp = $concept->getPropertySingleValue(OpenSkos::DATE_DELETED)->getValue();
            } else {
                $datestamp = $concept->getPropertySingleValue(DcTerms::MODIFIED)->getValue();
            }
        }
        
        $setSpecs = [];
        $specs = $this->setsMap->fetchTenantSpecData($concept);
        foreach ($specs as $spec) {
            $setSpecs[] = $spec['tenantcode'];
            $setSpecs[] = $spec['tenantcode'] . ':' . $spec['setcode'];
            $schemes = $this->setsMap->getSchemes($spec['tenantcode'], $spec['seturi'], $concept->getProperty(Skos::INSCHEME));
            foreach ($schemes as $scheme) {
                $setSpecs[] = $spec['tenantcode'] . ':' . $spec['setcode']. ':' . $scheme->getUuid()->getValue();
            }
        }
        
        return new Header($concept->getUri(), $datestamp, $setSpecs, $concept->isDeleted());
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
