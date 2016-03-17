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
 * Provide backwards compatability to the API output from OpenSKOS 1 as much as possible
 */
class DataRdf
{
    /**
     * @var Resource
     */
    private $resource;
    
    /**
     * @var bool
     */
    private $includeRdfHeader = true;
    
    /**
     * @var array
     */
    private $propertiesList;
    
    /**
     * @param Resource $concept
     * @param bool $includeRdfHeader
     * @param array $propertiesList Properties to serialize.
     */
    public function __construct(Resource $resource, $includeRdfHeader = true, $propertiesList = null)
    {
        $this->resource = $resource;
        $this->includeRdfHeader = $includeRdfHeader;
        $this->propertiesList = $propertiesList;
        
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
        if (!empty($this->propertiesList)) {
            $reducedResource = new Resource($this->resource->getUri());
            foreach ($this->resource->getProperties() as $property => $values) {
                if ($this->doIncludeProperty($property)) {
                    $reducedResource->setProperties($property, $values);
                }
            }
        } else {
            $reducedResource = $this->resource;
        }
        //var_dump('transform');
        //var_dump($reducedResource);
        $resource = \OpenSkos2\Bridge\EasyRdf::resourceToGraph($reducedResource);
        //var_dump('transform2');
        //var_dump($resource);
        return $resource->serialise(
            'rdfxml_openskos',
            [EasyRdfOpenSkos::OPTION_RENDER_ITEMS_ONLY => !$this->includeRdfHeader]
        );
    }
    
    /**
     * Should the property be included in the serialized data.
     * @param string $property
     * @return bool
     */
    protected function doIncludeProperty($property)
    {
        return empty($this->propertiesList) || in_array($property, $this->propertiesList);
    }
}
