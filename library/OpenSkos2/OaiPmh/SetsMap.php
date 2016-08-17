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

use \OpenSkos2\ConceptSchemeManager;
use \OpenSkos2\SetManager;

/**
 * Used to get tenant:set:schema sets.
 */
class SetsMap
{
    /**
     *
     * @var ConceptSchemeManager
     */
    protected $schemeManager;
    
    
    /**
     *
     * @var SetSchemeManager
     */
    protected $setManager;
    
    /**
     * Stores map from tenants to sets.
     * @var array
     */
    protected $tenantsToSets = [];
    
    /**
     * Stores map from tenants and sets to schemes.
     * @var array
     */
    protected $setsToSchemes = [];
    
    /**
     * @param ConceptSchemeManager $schemeManager
     * @param \OpenSKOS_Db_Table_Sets $setsModel
     */
    public function __construct(ConceptSchemeManager $schemeManager, SetManager $setManager)
    {
        $this->schemeManager = $schemeManager;
        $this->setManager = $setManager;
    }
    
    /**
     * Get data for sets
     * @param string $tenantCode
     * @param array $setsUris
     * @return array
     */
    public function getSets($tenantCode, $setsUris)
    {
        if (!isset($this->tenantsToSets[$tenantCode])) {
            $this->tenantsToSets[$tenantCode] = $this->setManager->getUrisMap($tenantCode);
        }
        
        $sets = [];
        foreach ($setsUris as $setUri) {
            if (isset($this->tenantsToSets[$tenantCode][$setUri])) {
                $sets[] = $this->tenantsToSets[$tenantCode][$setUri];
            }
        }
            
        return $sets;
    }
    
    /**
     * Get data for schemes
     * @param string $tenantCode
     * @param string $setUri
     * @param array $schemesUris
     * @return array
     */
    public function getSchemes($tenantCode, $setUri, $schemesUris)
    {
        if (!isset($this->setsToSchemes[$tenantCode])) {
            $this->setsToSchemes[$tenantCode] = [];
        }
        
        if (!isset($this->setsToSchemes[$tenantCode][$setUri])) {
            $allSchemes = $this->schemeManager->getSchemeBySetUri($setUri);
            foreach ($allSchemes as $scheme) {
                $this->setsToSchemes[$tenantCode][$setUri][$scheme->getUri()] = $scheme;
            }
        }
        
        $schemes = [];
        foreach ($schemesUris as $schemeUri) {
            if (isset($this->setsToSchemes[$tenantCode][$setUri][$schemeUri->getUri()])) {
                $schemes[] = $this->setsToSchemes[$tenantCode][$setUri][$schemeUri->getUri()];
            }
        }
        return $schemes;
    
    }
    
    public function fetchTenantSpecData($concept){
        return $this->setManager->fetchTenantSpec($concept);
    }
            
}
