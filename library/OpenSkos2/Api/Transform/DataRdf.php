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

namespace OpenSkos2\Api\Transform;

use OpenSkos2\EasyRdf\Serialiser\RdfXml\OpenSkosAsDescriptions as EasyRdfOpenSkos;
use OpenSkos2\Rdf\Resource;

/**
 * Transform Resource to a RDF string.
 * Provide backwards compatibility to the API output from OpenSKOS 1 as much as possible
 */
class DataRdf
{
    /**
     * @var Resource
     */
    private $concept;

    /**
     * @var bool
     */
    private $includeRdfHeader = true;

    /**
     * @var array
     */
    private $propertiesList;
    
    /**
     * @var array
     */
    private $excludePropertiesList;

    /**
     * @param Resource $concept
     * @param bool $includeRdfHeader
     * @param array $propertiesList Properties to serialize.
     */
    public function __construct(
            Resource $concept,
            $includeRdfHeader = true,
            $propertiesList = null,
            $excludePropertiesList = [])
    {
        $this->concept = $concept;
        $this->includeRdfHeader = $includeRdfHeader;
        $this->propertiesList = $propertiesList;
        $this->excludePropertiesList = $excludePropertiesList;

        // @TODO - put it somewhere globally
        \EasyRdf\Format::registerSerialiser(
            'rdfxml_openskos',
            '\OpenSkos2\EasyRdf\Serialiser\RdfXml\OpenSkosAsDescriptions'
        );
    }

    /**
     * Transform the concept to xml string
     *
     * @return string
     */
    public function transform()
    {
        if (!empty($this->propertiesList) || !empty($this->excludePropertiesList)) {
            $reducedResource = new Resource($this->concept->getUri());
            foreach ($this->concept->getProperties() as $property => $values) {
                if ($this->doIncludeProperty($property)) {
                    $reducedResource->setProperties($property, $values);
                }
            }
        } else {
            $reducedResource = $this->concept;
        }
        
        $resourceTypes = [
            \OpenSkos2\Concept::TYPE
        ];
        
        $concept = \OpenSkos2\Bridge\EasyRdf::resourceToGraph($reducedResource);
        return $concept->serialise(
            'rdfxml_openskos',
            [
                EasyRdfOpenSkos::OPTION_RENDER_ITEMS_ONLY => !$this->includeRdfHeader,
                EasyRdfOpenSkos::OPTION_RESOURCE_TYPES_TO_SERIALIZE => $resourceTypes
            ]
        );
    }

    /**
     * Should the property be included in the serialized data.
     * @param string $property
     * @return bool
     */
    protected function doIncludeProperty($property)
    {
        //The exclude list specifies properties which properties should be skipped
        //If a property is both in the include and exclude list we throw an error
        
        if (empty($this->propertiesList)) {
            if (in_array($property, $this->excludePropertiesList) === false) {
                return true;
            } else {
                return false;
            }
        }
        
        if (in_array($property, $this->propertiesList) === true) {
            if (in_array($property, $this->excludePropertiesList) === false) {
                return true;
            } else {
                throw new \OpenSkos2\Exception\InvalidArgumentException(
                        'The property ' . $property . ' is present both in the include and exclude lists');
            }
        }
    }
}
