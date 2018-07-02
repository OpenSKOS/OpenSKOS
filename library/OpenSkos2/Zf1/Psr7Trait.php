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

namespace OpenSkos2\Zf1;

/**
 * This trait is only to be used in a ZF 1 controller
 * to allow working with PSR7 request / responses with as goal
 * migrate away from ZF 1 eventually
 */
trait Psr7Trait
{

    /**
     * Get PSR7 request
     *
     * @return Psr\Http\Message\ServerRequestInterface
     */
    public function getPsrRequest()
    {
        // Add ZF 1 body to for requests other then POST
        // for < PHP 5.6 support see: http://php.net/manual/en/wrappers.php.php#wrappers.php.input
        $stream = new \Zend\Diactoros\Stream('php://memory', 'r+');
        $stream->write($this->getRequest()->getRawBody());

        return \Zend\Diactoros\ServerRequestFactory::fromGlobals()
                ->withBody($stream)
                ->withParsedBody($this->getParsedBody());
    }

    /**
     * Emit PSR7 Response
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     */
    public function emitResponse(\Psr\Http\Message\ResponseInterface $response)
    {
        (new \Zend\Diactoros\Response\SapiEmitter())->emit($response);
        exit; // find better way to prevent output from zf1
    }

    /**
     * Gets request parsed body
     * @return array
     */
    protected function getParsedBody()
    {
        $parsedBody = [];
        parse_str(urldecode($this->getRequest()->getRawBody()), $parsedBody);

        return $parsedBody;
    }
}
