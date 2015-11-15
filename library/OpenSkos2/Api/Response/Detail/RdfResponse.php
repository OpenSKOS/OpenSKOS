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
class RdfResponse extends DetailResponse
{
    /**
     * Get response
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getResponse()
    {
        $stream = new \Zend\Diactoros\Stream('php://memory', 'wb+');
        $concept = (new \OpenSkos2\Api\Transform\DataRdf($this->concept, true, $this->propertiesList))->transform();
        $stream->write($concept);
        $response = (new \Zend\Diactoros\Response())
            ->withBody($stream)
            ->withHeader('Content-Type', 'text/xml; charset=UTF-8');

        return $response;
    }
}
