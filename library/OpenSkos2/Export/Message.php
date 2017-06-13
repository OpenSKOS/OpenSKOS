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
namespace OpenSkos2\Export;
use OpenSkos2\Tenant;
class Message
{
    protected $format;
    protected $maxDepth;
    protected $outputFilePath;
    protected $propertiesToExport;
    
    protected $searchOptions;
    protected $uris;
    
    protected $tenant;
    
    public function getFormat()
    {
        return $this->format;
    }
    public function getMaxDepth()
    {
        return $this->maxDepth;
    }
    public function getOutputFilePath()
    {
        return $this->outputFilePath;
    }
    public function getPropertiesToExport()
    {
        return $this->propertiesToExport;
    }
    
    public function setFormat($format)
    {
        $this->format = $format;
    }
    public function setMaxDepth($maxDepth)
    {
        $this->maxDepth = $maxDepth;
    }
    public function setOutputFilePath($outputFilePath)
    {
        $this->outputFilePath = $outputFilePath;
    }
    public function setPropertiesToExport($propertiesToExport)
    {
        $this->propertiesToExport = $propertiesToExport;
    }
    public function getSearchOptions()
    {
        return $this->searchOptions;
    }
    public function getUris()
    {
        return $this->uris;
    }
    public function setSearchOptions($searchOptions)
    {
        $this->searchOptions = $searchOptions;
    }
    public function setUris($uris)
    {
        $this->uris = $uris;
    }
    
    /**
     * @return Tenant
     */
    public function getTenant()
    {
        return $this->tenant;
    }
    /**
     * @param Tenant $tenant
     */
    public function setTenant(Tenant $tenant)
    {
        $this->tenant = $tenant;
    }
    public function __construct(Tenant $tenant, $format, $propertiesToExport, $maxDepth, $outputFilePath = null)
    {
        $this->tenant = $tenant;
        $this->format = $format;
        $this->propertiesToExport = $propertiesToExport;
        $this->maxDepth = $maxDepth;
        $this->outputFilePath = $outputFilePath;
    }
}