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
 * Provide the json output for find-concepts api
 */
class JsonpResponse extends DetailResponse
{
    /**
     * JSONP Callback
     * @var string
     */
    private $callback;

    /**
     * @param \OpenSkos2\Concept $concept
     * @param string $callback
     * @param \OpenSkos2\Concept $propertiesList Properties to serialize.
     */
    public function __construct(\OpenSkos2\Concept $concept, $callback, $propertiesList = null)
    {
        $this->concept = $concept;
        $this->propertiesList = $propertiesList;
        $this->callback = $callback;
    }
    
    /**
     * Get response
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getResponse()
    {
        $stream = new \Zend\Diactoros\Stream('php://memory', 'wb+');
        $body = (new \OpenSkos2\Api\Transform\DataArray($this->concept, $this->propertiesList))->transform();
        $jsonp = $this->callback . '(' . json_encode($body) . ');';
        $stream->write($jsonp);
        $response = (new \Zend\Diactoros\Response($stream))
            ->withHeader('Content-Type', 'application/javascript');
        return $response;
    }
}
