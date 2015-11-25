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
use OpenSkos2\ConceptScheme;

class Editor_Models_ConceptSchemesCache
{
    const CONCEPT_SCHEMES_CACHE_KEY = 'CONCEPT_SCHEMES_CACHE_KEY';
    
    /**
     * @var ConceptSchemeManager 
     */
    protected $manager;
    
    /**
     * @var Zend_Cache_Core 
     */
    protected $cache;
    
    /**
     * @param ConceptSchemeManager $manager
     * @param Zend_Cache_Core $cache
     */
    public function __construct(ConceptSchemeManager $manager, Zend_Cache_Core $cache)
    {
        $this->manager = $manager;
        $this->cache = $cache;
    }
    
    /**
     * Fetches all schemes.
     * @return ConceptSchemeCollection
     */
    public function fetchAll($tenant = null)
    {
        // @TODO tenant
        $schemes = $this->cache->load(self::CONCEPT_SCHEMES_CACHE_KEY . $tenant);
        if ($schemes === false) {
            if (empty($tenant)) {
                $schemes = $this->manager->fetch();
            } else {
                $schemes = $this->manager->fetch([OpenSkos::TENANT => $tenant]);
            }
            $this->cache->save($schemes, self::CONCEPT_SCHEMES_CACHE_KEY . $tenant);
        }
        return $schemes;
    }
    
    /**
     * Fetches uri -> scheme map.
     * @return ConceptScheme[]
     */
    public function fetchUrisMap($tenant = null)
    {
        $shemes = $this->fetchAll($tenant);
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
    public function fetchUrisCaptionsMap($tenant = null, $inCollections = [])
    {
        $shemes = $this->fetchAll($tenant);
        $result = [];
        foreach ($shemes as $scheme) {
            $result[$scheme->getUri()] = $scheme->getCaption();
        }
        return $result;
    }
    
    /**
     * Fetches array with concept schemes meta data.
     * @param array $shemesUris
     * @return array
     */
    public function fetchConceptSchemesMeta($shemesUris, $tenant = null)
    {
        $shemes = $this->fetchAll($tenant);
        $result = [];
        foreach ($shemesUris as $uri) {
            $scheme = $shemes->findByUri($uri);
            $schemeMeta = $scheme->toFlatArray([
                'uri',
                'caption',
                DcTerms::TITLE
            ]);
            $schemeMeta['iconPath'] = ConceptScheme::buildIconPath($scheme->getPropertyFlatValue(OpenSkos::UUID));
            $result[] = $schemeMeta;
        }
        return $result;
    }
}
