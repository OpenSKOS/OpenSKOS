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
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Concept as SkosConcept;
use Picturae\OaiPmh\Implementation\Record\Header;
use Picturae\OaiPmh\Interfaces\Record;
use OpenSkos2\Api\Transform\DataRdf;
use OpenSkos2\OaiPmh\Repository;

// Meertens: OpenSKOS:TENANT and OpenSKOS:SET are derived properties for a concept.
// therefore obtaining them in "getHeader" for Meertens differ's from Picturae's version.
// Picturae's  changes from 23/11/2016 are taken.

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
     *  @var string $metadataFormat
     */
    protected $metadataFormat;

    /**
     * @param SkosConcept $concept
     * @param \OpenSkos2\OaiPmh\SetsMap $setsMap
     */
    public function __construct(SkosConcept $concept, SetsMap $setsMap, $metadataFormat = null)
    {
        $this->concept = $concept;
        $this->setsMap = $setsMap;
        $this->metadataFormat = $metadataFormat;
    }

    /**
     * Get header
     * @return Header
     */
    public function getHeader()
    {
        $concept = $this->concept;
        if (!$concept->isDeleted()) {
            $datestamp = $concept->getLatestModifyDate();
            // Meertens: the fallback is necessary for us because we do not always have lastmodifies in a concept
            if ($datestamp == null) {
                $datestamp = $concept->getDateSubmitted()->getValue();
            }
        } else {
            if ($concept->hasProperty(OpenSkos::DATE_DELETED)) {
                $datestamp = $concept->getPropertySingleValue(OpenSkos::DATE_DELETED)->getValue();
            } else {
                $datestamp = $concept->getLatestModifyDate();
            }
        }

        $setSpecs = [];
        $specs = $this->setsMap->fetchTenantSpecData($concept);
        foreach ($specs as $spec) {
            $setSpecs[] = $spec['tenantcode'];
            $setSpecs[] = $spec['tenantcode'] . ':' . $spec['setcode'];
            $schemes = $this->setsMap->getSchemes(
                $spec['tenantcode'],
                $spec['seturi'],
                $concept->getProperty(Skos::INSCHEME)
            );
            foreach ($schemes as $scheme) {
                $setSpecs[] = $spec['tenantcode'] . ':' . $spec['setcode'] . ':' .
                    $scheme->getUuid()->getValue();
            }
        }

        /*
         * @TODO: Fix once migration works correctly
         * We should be able to just fetch a single value, but due to bad data in the old system we will
         * just return the first value. The migration script will be fixed. Once this is done the first line
         * of code can be used.
         */
        // $uuid = $concept->getPropertySingleValue(OpenSKOS::UUID);
        $uuid = $concept->getProperty(OpenSKOS::UUID)[0]->getValue();
        return new Header(
            $uuid,
            $datestamp,
            $setSpecs,
            $concept->isDeleted()
        );
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
            (new DataRdf(
                $this->concept,
                true,
                null,
                $this->getExcludeProperties()
            ))->transform()
        );

        return $metadata;
    }

    /**
     * @return DomDocument|null
     */
    public function getAbout()
    {
    }
    
    /**
     * @return \OpenSkos2\Concept
     */
    public function getConcept()
    {
        return $this->concept;
    }
    
    /**
     * Get a list of exclude properties based on metadata format requested
     * @param \OpenSKOS_Db_Table_Row_Tenant $tenant
     * @param \Zend\Diactoros\ServerRequest $request
     */
    private function getExcludeProperties()
    {
        $metadataFormat = $this->metadataFormat;
        
        if ($metadataFormat === Repository::PREFIX_OAI_RDF) {
            return \OpenSkos2\Concept::$classes['SkosXlLabels'];
        } elseif ($metadataFormat === Repository::PREFIX_OAI_RDF_XL) {
            return \OpenSkos2\Concept::$classes['LexicalLabels'];
        } else {
            return [];
        }
    }
}
