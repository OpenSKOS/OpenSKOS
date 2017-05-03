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

namespace OpenSkos2\Api\Response;
/**
 * Provide the json output for find-concepts api
 */
abstract class DetailResponse implements \OpenSkos2\Api\Response\ResponseInterface
{

    /**
     * @var \OpenSkos2\Rdf\Resource
     */
    protected $resource;

    /**
     * @var []
     */
    protected $propertiesList;
    
    protected $resourceType;
    
  

    /**
     * @param \OpenSkos2\Rdf\Resource $resource
     * @param array $propertiesList Properties to serialize.
     */
    public function __construct(\OpenSkos2\Rdf\Resource $resource, $rdfType, $propertiesList = [])
    {
        $this->resource = $resource;
        $this->propertiesList = $propertiesList;
        $this->resourceType = $rdfType;
    }   
}
