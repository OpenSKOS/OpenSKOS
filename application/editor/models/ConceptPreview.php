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


use OpenSkos2\Namespaces;
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\ConceptCollection;

class Editor_Models_ConceptPreview
{
    /**
     * @var Editor_Models_ConceptSchemesCache 
     */
    protected $schemesCache;
    
    /**
     * @param Editor_Models_ConceptSchemesCache $schemesCache
     */
    public function __construct(Editor_Models_ConceptSchemesCache $schemesCache)
    {
        $this->schemesCache = $schemesCache;
    }
    
    /**
     * Converts the concept collection to basic preview data normally used for links.
     * Includes uri, caption, status, skope note, shemes
     * @param ConceptCollection $concepts
     * @return array
     */
    public function convertToLinksData(ConceptCollection $concepts)
    {
        $linksData = [];
        foreach ($concepts as $concept) {
            $conceptData = $concept->toFlatArray([
                'uri',
                'caption',
                OpenSkos::STATUS,
                Skos::SCOPENOTE
            ]);
            
            $conceptData['schemes'] = $this->schemesCache->fetchConceptSchemesMeta(
                $concept->getProperty(Skos::INSCHEME)
            );
            
            $linksData[] = $conceptData;
        }
        
        return $linksData;
    }


    /**
     * Converts the concept collection to basic preview data normally used for links.
     * This is an adapted version using data from Solr, instead of from Jena.
     * Includes uri, caption, status, skope note, shemes
     * @param ConceptCollection $concepts
     * @return array
     */
    public function convertSolrToLinksData(array $concepts)
    {
        $linksData = [];
        foreach ($concepts as $concept) {
            /*
            $conceptData = $concept->toFlatArray([
                'uri',
                'caption',
                OpenSkos::STATUS,
                Skos::SCOPENOTE
            ]);
            */
            $conceptData = array(
                'uri' => $concept['uri'],
                'caption' => $concept['prefLabel'][0],
                Namespaces::shortenProperty(OpenSkos::STATUS) => $concept['status'][0],
                Namespaces::shortenProperty(Skos::SCOPENOTE) => $concept['scopeNote'][0],
            );

            $conceptData['schemes'] = $this->schemesCache->fetchConceptSchemesMeta(
                $concept['inScheme']
            );

            $linksData[] = $conceptData;
        }

        return $linksData;
    }
}
