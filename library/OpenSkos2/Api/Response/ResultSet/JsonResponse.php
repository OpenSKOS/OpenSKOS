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

/**
 * Provide the json output for find-concepts api
 */
class JsonResponse implements \OpenSkos2\Api\Response\ResponseInterface
{

    /**
     * @var \OpenSkos2\Api\ConceptResultSet
     */
    private $result;

    /**
     *
     * @param \OpenSkos2\Api\ConceptResultSet $result
     */
    public function __construct(\OpenSkos2\Api\ConceptResultSet $result)
    {
        $this->result = $result;
    }

    /**
     * Get response
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getResponse()
    {
        $response = [
            'response' => [
                'numFound' => $this->result->getTotal(),
                'docs' => $this->getDocs()
            ]
        ];

        return new \Zend\Diactoros\Response\JsonResponse($response);
    }

    /**
     * Get docs property response
     *
     * @return array
     */
    private function getDocs()
    {
        $docs = [];
        foreach ($this->result->getConcepts() as $concept) {
            $nConcept = (new \OpenSkos2\Api\Transform\DataArray($concept))->transform();
            $docs[] = json_encode($nConcept);
        }

        return $docs;
    }
}
