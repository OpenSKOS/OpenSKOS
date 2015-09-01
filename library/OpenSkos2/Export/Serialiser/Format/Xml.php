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

namespace OpenSkos2\Export\Serialiser\Format;

use OpenSkos2\Rdf\Resource;
use OpenSkos2\Export\Serialiser\FormatAbstract;
use OpenSkos2\EasyRdf\Serialiser\RdfXml\OpenSkosAsDescriptions as EasyRdfOpenSkos;

// @TODO This class ignores properties to export.
class Xml extends FormatAbstract
{
    /**
     * Gets array of namespaces which are used in the collection which will be serialised.
     * @var array
     */
    public function getNamespaces()
    {
        if (empty($this->namespaces)) {
            throw new RequiredNamespacesListException(
                'Namespaces are not specified. Can not export to xml.'
            );
        }
        return $this->namespaces;
    }
    
    public function __construct()
    {
        // @TODO - put it somewhere globally
        \EasyRdf_Format::registerSerialiser(
            'rdfxml_openskos',
            '\OpenSkos2\EasyRdf\Serialiser\RdfXml\OpenSkosAsDescriptions'
        );
    }
    
    /**
     * Creates the header of the output.
     * @return string
     */
    public function printHeader()
    {
        $namespaces = [];
        foreach ($this->getNamespaces() as $key => $uri) {
            $namespaces[] = 'xmlns:' . $key . '="' . $uri . '"';
            
            // @TODO - put it somewhere globally
            \EasyRdf_Namespace::set($key, $uri);
        }
        
        return '<?xml version="1.0" encoding="utf-8" ?>' . PHP_EOL
            . '<rdf:RDF ' . implode(PHP_EOL, $namespaces) . '>' . PHP_EOL;
    }
    
    /**
     * Serialises a single resource.
     * @return string
     */
    public function printResource(Resource $resource)
    {
        $graph = \OpenSkos2\Bridge\EasyRdf::resourceToGraph($resource);
        return $graph->serialise(
            'rdfxml_openskos',
            [EasyRdfOpenSkos::OPTION_RENDER_ITEMS_ONLY => true]
        );
    }
    
    /**
     * Creates the footer of the output.
     * @return string
     */
    public function printFooter()
    {
        return '</rdf:RDF>';
    }
}
