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

use OpenSkos2\SetManager;
use OpenSkos2\SetCollection;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Rdf\Literal;
use OpenSkos2\ConceptScheme;
use OpenSkos2\Exception\OpenSkosException;

class Editor_Models_SetsCache
{
    const SET_CACHE_KEY = 'SET_CACHE_KEY';
    
    /**
     * @var string 
     */
    protected $tenantCode;
    
    /**
     * @var SetManager
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
     * @function getMyCacheKey Returns the cache key this class will use
     * @return string The Cache key
     */
    private function getMyCacheKey(){
        //If there's an instance uuid, get that first
        $resources = OpenSKOS_Application_BootstrapAccess::getOption('resources');
        $uuid = '';

        if(isset($resources['cachemanager']['general']['instance_uuid'])){
            $uuid = $resources['cachemanager']['general']['instance_uuid'];
        }
        $tenantCode = $this->requireTenantCode();
        $cache_key = sprintf("%s_%s_%s", $uuid, self::SET_CACHE_KEY, $tenantCode);  ;

        //There are restrictions on permitted cache keys
        $cache_key = preg_replace('#[^a-zA-Z0-9_]#', '_', $cache_key);

        return $cache_key;
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
        //Have to strip some characters from the cache
        $tenantCode = preg_replace('#[^a-zA-Z0-9_]#', '_', $this->tenantCode);

        return $tenantCode;
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
     * @param SetManager $manager
     * @param Zend_Cache_Core $cache
     */
    public function __construct(SetManager $manager, Zend_Cache_Core $cache)
    {
        $this->manager = $manager;
        $this->cache = $cache;
    }
    
    /**
     * Clears the Set cache.
     */
    public function clearCache()
    {
        /*
         * Switched to Memcache. A flush all had too many consequences, so switch remove and be alert for the
         *  consequences
         */
        $cache_key = $this->getMyCacheKey();
        $this->cache->remove($cache_key);
    }
    
    /**
     * Fetches all sets.
     * @return SetCollection
     */
    public function fetchAll()
    {

        $cache_key = $this->getMyCacheKey();
        $sets = $this->cache->load($cache_key);
        if ($sets === false) {
            $sets = $this->manager->fetch(
                [OpenSkos::TENANT => new Literal($this->requireTenantCode())],
                null,
                null,
                true
            );
            $this->cache->save($sets, $cache_key);
        }
        return $sets;
    }
    
    /**
     * Fetches uri -> scheme map.
     * @return ConceptScheme[]
     */
    public function fetchUrisMap()
    {
        $sets = $this->fetchAll();
        $result = [];
        foreach ($sets as $set) {
            $result[$set->getUri()] = $set;
        }
        return $result;
    }
    
    /**
     * Fetches uri -> caption map.
     * @return Set[]
     */
    public function fetchUrisCaptionsMap($inCollections = [])
    {
        $allSets = $this->fetchAll();
        $result = [];
        foreach ($allSets as $set) {
            if (empty($inCollections) || in_array($set->getSet(), $inCollections)) {
                $result[$set->getUri()] = $set->getCaption();
            }
        }
        return $result;
    }
    
    /**
     * Fetches array with sets meta data.
     * @param array $setsUris
     * @return array
     */
    public function fetchConceptSetsMeta($setsUris)
    {
        $sets = $this->fetchAll();

        foreach ($setsUris as $uri) {
            $set = $sets->findByUri($uri);
            if ($set) {
                $setMeta = $set->toFlatArray([
                    'uri',
                    'caption',
                    DcTerms::TITLE
                ]);
                $setMeta['iconPath'] = $set->getIconPath();
                $result[] = $setMeta;
            }
        }
        return $result;
    }
}
