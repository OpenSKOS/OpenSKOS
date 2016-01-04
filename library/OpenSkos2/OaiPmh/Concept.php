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
use OpenSkos2\ConceptSchemeManager;
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
     *
     * @var ConceptSchemeManager
     */
    protected $schemeManager;
    
    /**
     *
     * @var OpenSKOS_Db_Table_Collections
     */
    protected $setsModel;
    
    /**
     *
     * @param SkosConcept $concept
     */
    public function __construct(
        SkosConcept $concept,
        ConceptSchemeManager $schemeManager,
        \OpenSKOS_Db_Table_Collections $setsModel
    ) {
        $this->concept = $concept;
        $this->schemeManager = $schemeManager;
        $this->setsModel = $setsModel;
    }
    
    /**
     * Get header
     * @return Header
     */
    public function getHeader()
    {
        $concept = $this->concept;
        
        if (!$concept->isDeleted()) {
            $datestamp = $concept->getProperty(DcTerms::MODIFIED)[0]->getValue();
        } else {
            $datestamp = $concept->getPropertySingleValue(OpenSkos::DATE_DELETED)->getValue();
        }
        
        $setSpecs = [];
        
        // @TODO optimize.
        // Really needs to be optimized. Takes more than 10 seconds to do it for all concepts in the result
        
        $tenants = $concept->getProperty(OpenSkos::TENANT);
        
        $sets = [];
        foreach ($concept->getProperty(OpenSkos::SET) as $setUri) {
            $set = $this->setsModel->findByUri($setUri);
            if (!isset($sets[$set->tenant])) {
                $sets[$set->tenant] = [];
            }
            $sets[$set->tenant][] = $set;
        }
        
        foreach ($tenants as $tenant) {
            $setSpecs[] = (string)$tenant;
            
            foreach ($sets[(string)$tenant] as $set) {
                $setSpecs[] = $tenant . ':' . $set->code;
                
                $schemes = $this->schemeManager->getSchemeByCollectionUri(
                    $set->uri,
                    $concept->getProperty(Skos::INSCHEME)
                );
                foreach ($schemes as $scheme) {
                    $setSpecs[] = $tenant . ':' . $set->code . ':' . $scheme->getUuid();
                }
            }
        }
        
        // End @TODO optimize
        
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
