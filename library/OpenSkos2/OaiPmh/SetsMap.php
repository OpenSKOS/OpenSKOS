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
     * @var \OpenSkos2\SetManager
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
     * @param \OpenSkos2\SetManager $setManager
     */
    public function __construct(ConceptSchemeManager $schemeManager, \OpenSkos2\SetManager $setManager)
    {
        $this->schemeManager = $schemeManager;
        $this->setManager = $setManager;
    }
    /**
     * Get data for sets
     * @param string $tenant
     * @param array $setsUris
     * @return array
     */
    public function getSets($tenant, $setsUris)
    {
        $tenant = (string)$tenant;
        if (!isset($this->tenantsToSets[$tenant])) {
            $this->tenantsToSets[$tenant] = $this->setManager->getUrisMap($tenant);
        }
        
        $sets = [];
        foreach ($setsUris as $setUri) {
            $setUri = (string)$setUri;
            if (isset($this->tenantsToSets[$tenant][$setUri])) {
                $sets[] = $this->tenantsToSets[$tenant][$setUri];
            }
        }
        return $sets;
    }
    /**
     * Get data for schemes
     * @param string $tenant
     * @param string $setUri
     * @param array $schemesUris
     * @return array
     */
    public function getSchemes($tenant, $setUri, $schemesUris)
    {
        $tenant = (string)$tenant;
        $setUri = (string)$setUri;
        if (!isset($this->setsToSchemes[$tenant])) {
            $this->setsToSchemes[$tenant] = [];
        }
        if (!isset($this->setsToSchemes[$tenant][$setUri])) {
            // @TODO tenant? in getSchemesByCollectionUri
            $allSchemes = $this->schemeManager->getSchemeBySetUri($setUri);
            foreach ($allSchemes as $scheme) {
                $this->setsToSchemes[$tenant][$setUri][$scheme->getUri()] = $scheme;
            }
        }
        $schemes = [];
        foreach ($schemesUris as $schemeUri) {
            $schemeUri = (string)$schemeUri;
            if (isset($this->setsToSchemes[$tenant][$setUri][$schemeUri])) {
                $schemes[] = $this->setsToSchemes[$tenant][$setUri][$schemeUri];
            }
        }
        return $schemes;
    }
}