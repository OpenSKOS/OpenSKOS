<?php
/**
 * Created by PhpStorm.
 * User: jsmit
 * Date: 28/08/2015
 * Time: 08:51
 */

namespace OpenSkos2;


use EasyRdf_Graph;
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
     * @return ResourceCollection
     */
    public function getResources()
    {
        $graph = new EasyRdf_Graph();
        $graph->parseFile($this->fileName);
        return \OpenSkos2\Bridge\EasyRdf::graphToResourceCollection($graph);
    }
}