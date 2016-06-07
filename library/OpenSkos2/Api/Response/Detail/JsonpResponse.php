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
class JsonpResponse extends DetailResponse
{
    /**
     * JSONP Callback
     * @var string
     */
    private $callback;

    /**
     * @param \OpenSkos2\Rdf\Resource $resource
     * @param string $callback
     * @param array $propertiesList Properties to serialize.
     */
    public function __construct(\OpenSkos2\Rdf\Resource $resource, $callback, $propertiesList = null)
    {
        $this->resource = $resource;
        $this->propertiesList = $propertiesList;
        $this->callback = $callback;
    }
    
    /**
     * Get response
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getResponseConcept()
    {
        $stream = new \Zend\Diactoros\Stream('php://memory', 'wb+');
        $body = (new \OpenSkos2\Api\Transform\DataArray($this->resource, $this->propertiesList))->transformConcept();
        $jsonp = $this->callback . '(' . json_encode($body) . ');';
        $stream->write($jsonp);
        $response = (new \Zend\Diactoros\Response($stream))
            ->withHeader('Content-Type', 'application/javascript');
        return $response;
    }
    
     /**
     * Get response
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getResponse()
    {
        $body = (new \OpenSkos2\Api\Transform\DataArray($this->resource, $this->propertiesList))->transform();
        $response = self::produceJsonPResponse($body, $this->callback);
        return $response;
    }
    
  
    // also used in autocomplete controller
      public static function produceJsonPResponse($body, $callback) {
        $stream = new \Zend\Diactoros\Stream('php://memory', 'wb+');
        $jsonp = $callback . '(' . json_encode($body) . ');';
        $stream->write($jsonp);
        $response = (new \Zend\Diactoros\Response($stream))
                ->withHeader('Content-Type', 'application/javascript');
        return $response;
    }

}
