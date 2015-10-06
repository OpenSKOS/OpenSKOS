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

/**
 * Provide the html output for find-concepts api
 */
class HTMLResponse implements \OpenSkos2\Api\Response\ResponseInterface
{

    /**
     * @var \EasyRdf\Graph
     */
    private $concept;

    /**
     * @param \OpenSkos2\Concept $concept
     */
    public function __construct(\OpenSkos2\Concept $concept)
    {
        $this->concept = $concept;
    }

    /**
     * Get response
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getResponse()
    {
        $stream = new \Zend\Diactoros\Stream('php://memory', 'wb+');
        $stream->write('<h1>OMG</h1>');
        return new \Zend\Diactoros\Response($stream);
    }
}
