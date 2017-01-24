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

use OpenSkos2\ConceptSchemeManager;
use OpenSkos2\ConceptSchemeCollection;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Rdf\Literal;
use OpenSkos2\ConceptScheme;
use OpenSkos2\Exception\OpenSkosException;

class Editor_Models_ConceptSchemesCache
{
    const CONCEPT_SCHEMES_CACHE_KEY = 'CONCEPT_SCHEMES_CACHE_KEY';
    
    /**
     * @var string 
     */
    protected $tenantCode;
    
    /**
     * @var ConceptSchemeManager 
     */
    protected $manager;
    
    /**
     * @var Zend_Cache_Core 
     */
    protected $cache;
    
    /**
     * Get tenant for which the cache is done.
     * @return string
     */
    public function getTenantCode()
    {
        return $this->tenantCode;
    }
    
    /**
     * Get tenant for which the cache is done.
     * @return string
     */
    public function requireTenantCode()
    {
        if (empty($this->tenantCode)) {
            throw new OpenSkosException('Tenant code is required for editor cache.');
        }
        return $this->tenantCode;
    }

    /**
     * Sets tenant for which the cache is.
     * @param string $tenantCode
     */
    public function setTenantCode($tenantCode)
    {
        $this->tenantCode = $tenantCode;
    }
    
    /**
     * @param string $tenantCode
     * @param ConceptSchemeManager $manager
     * @param Zend_Cache_Core $cache
     */
    public function __construct(ConceptSchemeManager $manager, Zend_Cache_Core $cache)
    {
        $this->manager = $manager;
        $this->cache = $cache;
    }
    
    /**
     * Clears the concept schemes cache.
     */
    public function clearCache()
    {
        $this->cache->clean();
    }
    
    /**
     * Fetches all schemes.
     * @return ConceptSchemeCollection
     */
    public function fetchAll()
    {
        $schemes = $this->cache->load(self::CONCEPT_SCHEMES_CACHE_KEY . $this->requireTenantCode());
        if ($schemes === false) {
            $schemes = $this->manager->fetch(
                [OpenSkos::TENANT => new Literal($this->requireTenantCode())],
                null,
                null,
                true
            );
            $this->cache->save($schemes, self::CONCEPT_SCHEMES_CACHE_KEY . $this->requireTenantCode());
        }
        return $schemes;
    }
    
    /**
     * Fetches uri -> scheme map.
     * @return ConceptScheme[]
     */
    public function fetchUrisMap()
    {
        $shemes = $this->fetchAll();
        $result = [];
        foreach ($shemes as $scheme) {
            $result[$scheme->getUri()] = $scheme;
        }
        return $result;
    }
    
    /**
     * Fetches uri -> caption map.
     * @return ConceptScheme[]
     */
    public function fetchUrisCaptionsMap($inCollections = [])
    {
        $shemes = $this->fetchAll();
        $result = [];
        foreach ($shemes as $scheme) {
            if (empty($inCollections) || in_array($scheme->getSet(), $inCollections)) {
                $result[$scheme->getUri()] = $scheme->getCaption();
            }
        }
        return $result;
    }
    
    /**
     * Fetches array with concept schemes meta data.
     * @param array $shemesUris
     * @return array
     */
    public function fetchConceptSchemesMeta($shemesUris)
    {
        $shemes = $this->fetchAll();
        $result = [];
        foreach ($shemesUris as $uri) {
            $scheme = $shemes->findByUri($uri);
            if ($scheme) {
                $schemeMeta = $scheme->toFlatArray([
                    'uri',
                    'caption',
                    DcTerms::TITLE
                ]);
                $schemeMeta['iconPath'] = $scheme->getIconPath();
                $result[] = $schemeMeta;
            }
        }
        return $result;
    }
}
