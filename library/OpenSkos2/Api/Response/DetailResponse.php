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
    
    /**
     * @var []
     */
    protected $excludePropertiesList;
    
    
      /**
      *
      * @var array
      */
     protected $auxVals=[];
     /**
      *
      * @var string 
      */
     protected $auxField=null;
     
     /**
      *
      * @var bool 
      */
     protected $backwardCompatible = false;
     
     
     
    /**
     * @param \OpenSkos2\Rdf\Resource $resource
     * @param array $propertiesList Properties to serialize.
     */
    public function __construct(\OpenSkos2\Rdf\Resource $resource, $propertiesList = null, $excludePropertiesList = [])
    {
        $this->resource = $resource;
        $this->propertiesList = $propertiesList;
        $this->excludePropertiesList = $excludePropertiesList;
    }
    
     
     public function setExtras($collection, $fieldname, $backwardCompatible){
         $this->auxVals = $collection;
         $this->auxField = $fieldname;
         $this->backwardCompatible = $backwardCompatible;
     }

   
    
    protected function addAuxToBody($body){
        if ($this->auxField != null) {
            if (count($this->auxVals) > 0) {
                foreach ($this->auxVals as $val) {
                    $body[$this->auxField][] = (new \OpenSkos2\Api\Transform\DataArray($val))->transform();
                }
            } else {
                $body[$this->auxField] = [];
            }
        }
        if ($this->backwardCompatible) {
            $correctedBody = (new BackwardCompatibility())->backwardCompatibilityMap(
                $body,
                $this->resource->getType()->getUri()
            );
        } else {
            $correctedBody = $body;
        }
        return $correctedBody;
    }
    
}