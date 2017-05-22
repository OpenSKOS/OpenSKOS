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
use OpenSkos2\Api\Response\BackwardCompatibility;
use OpenSkos2\Api\Transform\DataArray;

/**
 * Provide the json output for find-* api
 */
class JsonResponse extends DetailResponse
{

    protected $extraVals;
    protected $extraField;
    protected $init;

    public function __construct(
        \OpenSkos2\Rdf\Resource $resource,
        $rdfType,
        $propertiesList,
        $extraField = null,
        $extraVals = []
    ) {
        parent::__construct($resource, $rdfType, $propertiesList);
        $this->extraField = $extraField;
        $this->extraVals = $extraVals;
        $this->init = parse_ini_file(__DIR__ . '/../../../../../application/configs/application.ini');
    }

    /**
     * Get response
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getResponse()
    {
        $data = $this->getResponseData();
        return new \Zend\Diactoros\Response\JsonResponse($data);
    }

    protected function getResponseData()
    {
        $body = (new DataArray($this->resource, $this->propertiesList))->transform();
        if ($this->extraField != null) {
            if (count($this->extraVals) > 0) {
                foreach ($this->extraVals as $val) {
                    $body[$this->extraField][] = (new DataArray($val))->transform();
                }
            } else {
                $body[$this->extraField] = [];
            }
        }
        if ($this->init["custom.backward_compatible"]) {
            $correctedBody = (new BackwardCompatibility())->backwardCompatibilityMap(
                $body,
                $this->resourceType
            );
        } else {
            $correctedBody = $body;
        }
        return $correctedBody;
    }
}
