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

namespace OpenSkos2\Api\Response\ResultSet;

use OpenSkos2\Api\Response\ResultSet\JsonResponse;

/**
 * Provide the jsonp output for find-* api
 */
class JsonpResponse extends JsonResponse
{

    /**
     * JSONP Callback
     * @var string
     */
    protected $callback;

    /**
     * @param \OpenSkos2\Api\ResourceResultSet $result
     * @param string $callback
     * @param array $propertiesList Properties to serialize.
     */
    public function __construct(\OpenSkos2\Api\ResourceResultSet $result, $rdfType, $callback, $propertiesList = null)
    {
        parent::__construct($result, $rdfType, $propertiesList);
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
        $stream->write(
            $this->callback . '(' . json_encode($this->getResponseData()) . ');'
        );

        return (new \Zend\Diactoros\Response($stream))
                ->withHeader('Content-Type', 'application/javascript');
    }
}
