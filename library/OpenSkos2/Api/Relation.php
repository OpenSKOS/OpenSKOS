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

namespace OpenSkos2\Api;

class Relation
{
    use Response\ApiResponseTrait;

    /**
     * @var \OpenSkos2\ConceptManager
     */
    private $manager;

    /**
     * @param \OpenSkos2\ConceptManager $manager
     */
    public function __construct(\OpenSkos2\ConceptManager $manager)
    {
        $this->manager = $manager;
    }

    public function addRelation(\Psr\Http\Message\ServerRequestInterface $request)
    {
        try {
            $this->addConceptRelation($request);
        } catch (Exception\ApiException $exc) {
            return $this->getErrorResponse($exc->getCode(), $exc->getMessage());
        }

        $stream = new \Zend\Diactoros\Stream('php://memory', 'wb+');
        $stream->write('Relations added');
        $response = (new \Zend\Diactoros\Response())
            ->withBody($stream);
        return $response;
    }
    
    /**
     * Add concept relation
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @throws Exception\ApiException
     */
    private function addConceptRelation(\Psr\Http\Message\ServerRequestInterface $request)
    {
        $body = $request->getParsedBody();

        if (!isset($body['tenant'])) {
            throw new Exception\ApiException('Missing tenant', 400);
        }

        $tenant = $this->getTenant($body['tenant']);

        if (!isset($body['key'])) {
            throw new Exception\ApiException('Missing relation', 400);
        }

        $user = $this->getUserByKey($body['key']);

        if (!isset($body['concept'])) {
            throw new Exception\ApiException('Missing concept', 400);
        }

        $concept = $this->manager->fetchByUri($body['concept']);
        $this->conceptEditAllowed($concept, $tenant, $user);
        
        if (!isset($body['type']) || !is_string($body['type'])) {
            throw new Exception\ApiException('Missing type', 400);
        }

        if (!isset($body['related']) || !is_array($body['related'])) {
            throw new Exception\ApiException('Missing realted', 400);
        }

        try {
            $this->manager->addRelationBothsides($body['concept'], $body['type'], $body['related']);
        } catch (\Exception $exc) {
            throw new Exception\ApiException($exc->getMessage(), 500);
        }
    }
}
