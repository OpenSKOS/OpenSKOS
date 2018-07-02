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

/**
 * API Resource result set
 * The result set for the API needs the total items found
 */
class ResourceResultSet
{

    /**
     * Total
     * @var int
     */
    private $total;

    /**
     * Resource
     * @var \OpenSkos2\Rdf\ResourceCollection
     */
    private $resources;

    /**
     * Offset from total result
     *
     * @var int
     */
    private $start;

    /**
     * Number of rows limit
     *
     * @var int
     */
    private $limit;

    /**
     * @param \OpenSkos2\Rdf\ResourceCollection $resources
     * @param int $total
     * @param int $start
     * @param int $limit
     */
    public function __construct(\OpenSkos2\Rdf\ResourceCollection $resources, $total, $start, $limit)
    {
        $this->resources = $resources;
        $this->total = $total;
        $this->start = $start;
        $this->limit = $limit;
    }

    /**
     *
     * @return int
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     *
     * @return \OpenSkos2\Rdf\ResourceCollection
     */
    public function getResources()
    {
        return $this->resources;
    }

    /**
     * Get offset
     *
     * @return int
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }
}
