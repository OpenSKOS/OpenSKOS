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


class Message
{
//    protected $userId;
    
    protected $format;
    protected $maxDepth;
    protected $outputFilePath;
    protected $propertiesToExport;
    
//    protected $type;
    
    protected $query;
    
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

    public function getQuery()
    {
        return $this->query;
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

    public function setQuery($query)
    {
        $this->query = $query;
    }

    public function __construct($format, $query, $propertiesToExport, $maxDepth, $outputFilePath = null)
    {
        $this->format = $format;
        $this->query = $query;
        $this->propertiesToExport = $propertiesToExport;
        $this->maxDepth = $maxDepth;
        $this->outputFilePath = $outputFilePath;
    }
}
