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
    protected $manager;

    /**
     * @param \OpenSkos2\ConceptManager $manager
     */
    public function __construct(\OpenSkos2\ConceptManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return Zend\Diactoros\Response
     */
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
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return Zend\Diactoros\Response
     */
    public function deleteRelation(\Psr\Http\Message\ServerRequestInterface $request)
    {
        try {
            $this->deleteConceptRelation($request);
        } catch (Exception\ApiException $exc) {
            return $this->getErrorResponse($exc->getCode(), $exc->getMessage());
        }

        $stream = new \Zend\Diactoros\Stream('php://memory', 'wb+');
        $stream->write('Relation deleted');
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
    protected function addConceptRelation(\Psr\Http\Message\ServerRequestInterface $request)
    {
        $body = $request->getParsedBody();

        if (!isset($body['key'])) {
            throw new Exception\ApiException('Missing key', 400);
        }
        if (!isset($body['concept'])) {
            throw new Exception\ApiException('Missing concept', 400);
        }
        if (!isset($body['related'])) {
            throw new Exception\ApiException('Missing related', 400);
        }
        if (!isset($body['type'])) {
            throw new Exception\ApiException('Missing type', 400);
        }
        
        $user = $this->getUserByKey($body['key']);

        $concept = $this->manager->fetchByUri($body['concept']);
        $this->resourceEditAllowed($concept, $concept->getInstitution(), $user);

        $relatedConcept = $this->manager->fetchByUri($body['concept']);
        $this->resourceEditAllowed($relatedConcept, $relatedConcept->getInstitution(), $user);
        
        try {
            $this->manager->addRelation($body['concept'], $body['type'], $body['related']);
        } catch (\Exception $exc) {
            throw new Exception\ApiException($exc->getMessage(), 500);
        }
    }
    
    /**
     * Delete concept relation
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @throws Exception\ApiException
     */
    protected function deleteConceptRelation(\Psr\Http\Message\ServerRequestInterface $request)
    {
        $body = $request->getParsedBody();

        if (!isset($body['key'])) {
            throw new Exception\ApiException('Missing key', 400);
        }
        if (!isset($body['concept'])) {
            throw new Exception\ApiException('Missing concept', 400);
        }
        if (!isset($body['related'])) {
            throw new Exception\ApiException('Missing related', 400);
        }
        if (!isset($body['type'])) {
            throw new Exception\ApiException('Missing type', 400);
        }
        
        $user = $this->getUserByKey($body['key']);

        $concept = $this->manager->fetchByUri($body['concept']);
        $this->resourceEditAllowed($concept, $concept->getInstitution(), $user);

        $relatedConcept = $this->manager->fetchByUri($body['concept']);
        $this->resourceEditAllowed($relatedConcept, $relatedConcept->getInstitution(), $user);
        
        try {
            $this->manager->deleteRelation($body['concept'], $body['type'], $body['related']);
        } catch (\Exception $exc) {
            throw new Exception\ApiException($exc->getMessage(), 500);
        }
    }
}
