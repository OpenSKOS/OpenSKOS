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
namespace OpenSkos2\Api\Response\Detail;
use OpenSkos2\Api\Response\DetailResponse;
/**
 * Provide the json output for find-* api
 */
class JsonResponse extends DetailResponse

{
     /**
      *
      * @var array
      */
     private $auxVals=[];
     /**
      *
      * @var string 
      */
     private $auxField;
     
     /**
      *
      * @var bool 
      */
     private $backwardCompatible = false;
     
     
     
     public function setExtras($collection, $fieldname, $backwardCompatible){
         $this->auxVals = $collection;
         $this->auxField = $fieldname;
         $this->backwardCompatible = $backwardCompatible;
     }

    /**
     * Get response
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getResponse()
    {
        $body = (new \OpenSkos2\Api\Transform\DataArray(
            $this->resource,
            $this->propertiesList,
            $this->excludePropertiesList
        ))->transform();
        $correctedBody = $this->addAuxtoBody($body);
        return new \Zend\Diactoros\Response\JsonResponse($correctedBody);
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