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

namespace OpenSkos2\Export\Serialiser;

use OpenSkos2\Rdf\Resource;

abstract class FormatAbstract
{
    /**
     * Array of properties to be serialised.
     * @var array
     */
    protected $propertiesToSerialise = [];
    
    // @TODO Not all formats need the namespaces
    /**
     * Array of namespaces which are used in the collection which will be serialised.
     * @var array
     */
    protected $namespaces = [];
    
    /**
     * Max depth to export. Used for rtf format.
     * @var int
     */
    protected $maxDepth = 1;
    
    /**
     * @var array
     * @TODO this is not fully implemented everywhere
     */
    protected $excludePropertiesList;
    
    /**
     * Gets the array of properties to be serialised.
     * @return array
     */
    public function getPropertiesToSerialise()
    {
        return $this->propertiesToSerialise;
    }

    /**
     * Sets the array of properties to be serialised.
     * @param array $propertiesToSerialise
     */
    public function setPropertiesToSerialise($propertiesToSerialise)
    {
        $this->propertiesToSerialise = $propertiesToSerialise;
    }
    
    /**
     * Gets array of namespaces which are used in the collection which will be serialised.
     * @var array
     */
    public function getNamespaces()
    {
        return $this->namespaces;
    }

    /**
     * Sets array of namespaces which are used in the collection which will be serialised.
     * @var array
     */
    public function setNamespaces($namespaces)
    {
        $this->namespaces = $namespaces;
    }
    
    /**
     * Gets max depth to export. Used for rtf format.
     * @return int
     */
    public function getMaxDepth()
    {
        return $this->maxDepth;
    }

    /**
     * Sets max depth to export. Used for rtf format.
     * @param $maxDepth int
     */
    public function setMaxDepth($maxDepth)
    {
        $this->maxDepth = $maxDepth;
    }
    
    /**
     * @return array
     */
    public function getExcludePropertiesList()
    {
        return $this->excludePropertiesList;
    }

    /**
     * @param array $excludePropertiesList
     */
    public function setExcludePropertiesList($excludePropertiesList)
    {
        $this->excludePropertiesList = $excludePropertiesList;
    }
    
    /**
     * Creates the header of the output.
     * @return string
     */
    abstract public function printHeader();
    
    /**
     * Serialises a single resource.
     * @return string
     */
    abstract public function printResource(Resource $resource);
    
    /**
     * Creates the footer of the output.
     * @return string
     */
    abstract public function printFooter();
}
