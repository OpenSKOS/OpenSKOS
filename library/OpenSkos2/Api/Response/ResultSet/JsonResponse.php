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

use OpenSkos2\Api\Response\ResultSetResponse;

/**
 * Provide the json output for find-* api
 */
class JsonResponse extends ResultSetResponse
{
    /**
     * Get response
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getResponse()
    {
        return new \Zend\Diactoros\Response\JsonResponse($this->getResponseData());
    }
    
    /**
     * Gets the response data.
     * @return array
     */
    protected function getResponseData()
    {
        return [
            'response' => [
                'numFound' => $this->result->getTotal(),
                'rows' => $this->result->getLimit(),
                'start' => $this->result->getStart(),
                'docs' => $this->getDocs()
            ]
        ];
    }

    /**
     * Get docs property response
     *
     * @return array
     */
    protected function getDocs()
    {
        $docs = [];
        foreach ($this->result->getResources() as $resource) {
            $nResource = (new \OpenSkos2\Api\Transform\DataArray(
                $resource,
                $this->propertiesList,
                $this->excludePropertiesList
            ))->transform();
            $docs[] = $nResource;
        }

        return $docs;
    }
}
