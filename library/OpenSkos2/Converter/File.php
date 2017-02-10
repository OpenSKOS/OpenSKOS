<?php

/**
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

namespace OpenSkos2\Converter;

use EasyRdf\Graph;
use OpenSkos2\Rdf\ResourceCollection;

class File
{
    /**
     * @var string
     */
    protected $fileName;

    /**
     * File constructor.
     * @param string $fileName
     */
    public function __construct($fileName)
    {
        $this->fileName = $fileName;
    }

    /**
     * @param array $allowedChildrenTypes , optional, For example skos xl
     * @return ResourceCollection
     */
    public function getResources($allowedChildrenTypes = [])
    {
        $graph = new Graph();
        $graph->parseFile($this->fileName);
        return \OpenSkos2\Bridge\EasyRdf::graphToResourceCollection($graph, null, $allowedChildrenTypes);
    }
}
