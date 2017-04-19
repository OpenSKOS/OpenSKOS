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
use OpenSkos2\Api\Transform\DataArray;
/**
 * Provide the json output for find-* api
 */
class JsonResponse extends DetailResponse {

  /**
   * Get response
   *
   * @return \Psr\Http\Message\ResponseInterface
   */
  public function getResponse() {
    $body = (new DataArray($this->resource, $this->propertiesList))->transform();
    return new \Zend\Diactoros\Response\JsonResponse($body);
  }

  public function getExtendedResponse($fieldname, $vals) {
    $body = (new DataArray($this->resource, $this->propertiesList))->transform();
    foreach ($vals as $val){
      $body[$fieldname][] = (new DataArray($val))->transform();
    }
    if (BACKWARD_COMPATIBLE) {
      $correctedBody = $this->backwardCompatibilityMap($body);
    } else {
      $correctedBody = $body;
    }
    return new \Zend\Diactoros\Response\JsonResponse($correctedBody);
  }

}
