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

namespace OpenSkos2\EasyRdf\Serialiser\RdfXml;

use EasyRdf\Literal;
use EasyRdf\Resource;
use EasyRdf\Graph;
use OpenSkos2\Exception\InvalidArgumentException;
use OpenSkos2\Exception\OpenSkosException;

class OpenSkos extends \EasyRdf\Serialiser\RdfXml
{

    const OPTION_RENDER_ITEMS_ONLY = 'renderItemsOnly';
    const OPTION_RESOURCE_TYPES_TO_SERIALIZE = 'serializableResourceTypes';

    protected $objects = [];
    private $outputtedResources = array();

    /**
     * Method to serialise an EasyRdf\Graph to RDF/XML
     *
     * @param Graph  $graph  An EasyRdf\Graph object.
     * @param string $format The name of the format to convert to.
     * @param array  $options
     *
     * @return string The RDF in the new desired format.
     * @throws Exception
     */
    public function serialise(Graph $graph, $format, array $options = array())
    {

        //The older versions the EasyRdfSerialiser expected one argument here;
        // Newer versions want two
        try {
            parent::checkSerialiseParams($graph, $format);
        } catch (\InvalidArgumentException $e) {
            parent::checkSerialiseParams($format);
        }


        if ($format != 'rdfxml_openskos') {
            throw new OpenSkosException(
                "\\OpenSkos2\\EasyRdf\\Serialiser\\RdfXml\\OpenSkos does not support: {$format}"
            );
        }
        // store of namespaces to be appended to the rdf:RDF tag
        $this->prefixes = array('rdf' => true);
        // store of the resource URIs we have serialised
        $this->outputtedResources = array();
        // Serialise URIs first
        foreach ($graph->resources() as $resource) {
            if (!$resource->isBnode() && $this->shouldBeSerialized($resource, $options)) {
                /* @var $resource Resource */
                $this->rdfxmlResource($resource, true);
            }
        }
        // Serialise bnodes afterwards
        foreach ($graph->resources() as $resource) {
            if ($resource->isBnode()) {
                $this->rdfxmlResource($resource, true);
            }
        }
        // iterate through namepsaces array prefix and output a string.
        $namespaceStr = '';
        foreach ($this->prefixes as $prefix => $count) {
            $url = \EasyRdf\RdfNamespace::get($prefix);
            if (strlen($namespaceStr)) {
                $namespaceStr .= "\n        ";
            }
            if (strlen($prefix) === 0) {
                $namespaceStr .= ' xmlns="' . htmlspecialchars($url) . '"';
            } else {
                $namespaceStr .= ' xmlns:' . $prefix . '="' . htmlspecialchars($url) . '"';
            }
        }
        if (empty($options[self::OPTION_RENDER_ITEMS_ONLY])) {
            return "<?xml version=\"1.0\" encoding=\"utf-8\" ?>\n" .
                "<rdf:RDF" . $namespaceStr . ">\n" . implode("\n", $this->objects) . "\n</rdf:RDF>\n";
        } else {
            return implode(PHP_EOL, $this->objects);
        }
    }

    public function getObjectCount()
    {
        return count($this->objects);
    }

    /**
     * Protected method to serialise a whole resource and its properties
     * @ignore
     */
    protected function rdfxmlResource($res, $showNodeId, $depth = 1)
    {
        // Keep track of the resources we have already serialised
        if (isset($this->outputtedResources[$res->getUri()])) {
            return [];
        } else {
            $this->outputtedResources[$res->getUri()] = true;
        }

        // If the resource has no properties - don't serialise it
        $properties = $res->propertyUris();
        if (count($properties) == 0) {
            return [];
        }

        $xmlString = $this->getResourceXmlString($res, $showNodeId, $depth);
        $this->objects[] = str_replace('dc11:subject', 'dc:subject', $xmlString);
    }

    protected function getResourceXmlString($res, $showNodeId, $depth)
    {
        $properties = $res->propertyUris();

        // meertens patch
        if (!$res->isBNode()) { // bnodes does not have description tag
            $type = $this->determineResType($res);
            if ($type) {
                $this->addPrefix($type);
            } else {
                $type = 'rdf:Description';
            }


            $indent = str_repeat('  ', $depth);
            $xmlString = "\n$indent<$type";
            if ($res->isBNode()) { //??
                if ($showNodeId) {
                    $xmlString .= ' rdf:nodeID="' . htmlspecialchars($res->getBNodeId()) . '"';
                }
            } else {
                $xmlString .= ' rdf:about="' . htmlspecialchars($res->getUri()) . '"';
            }
            $xmlString .= ">\n";
        } else {
            $xmlString = "\n";
        }
        if ($res instanceof \EasyRdf\Container) {
            foreach ($res as $item) {
                $xmlString .= $this->rdfxmlObject('rdf:li', $item, $depth + 1);
            }
        } else {
            foreach ($properties as $property) {
                $short = \EasyRdf\RdfNamespace::shorten($property, true);
                if ($short) {
                    $this->addPrefix($short);
                    $objects = $res->all("<$property>");
                    if ($short == 'rdf:type' && $type != 'rdf:Description') {
                        array_shift($objects);
                    }
                    foreach ($objects as $object) {
                        $xmlString .= $this->rdfxmlObject($short, $object, $depth + 1);
                    }
                } else {
                    throw new OpenSkosException(
                        "It is not possible to serialse the property " .
                        "'$property' to RDF/XML."
                    );
                }
            }
        }

        if (!$res->isBNode()) { // bnodes does not have description tag
            $xmlString .= "$indent</$type>\n";
        }

        return $xmlString;
    }

    /**
     * Protected method to serialise an object node into an XML object
     * @ignore
     */
    protected function rdfxmlObject($property, $obj, $depth)
    {
        $indent = str_repeat('  ', $depth);
        if ($property[0] === ':') {
            $property = substr($property, 1);
        }
        if (is_object($obj) and $obj instanceof Resource) {
            $pcount = count($obj->propertyUris());
            $rpcount = $this->reversePropertyCount($obj);
            $alreadyOutput = isset($this->outputtedResources[$obj->getUri()]);
            $tag = "{$indent}<{$property}";
            if ($obj->isBNode()) {
                if ($alreadyOutput or $rpcount > 1 or $pcount == 0) {
                    $tag .= " rdf:nodeID=\"" . htmlspecialchars($obj->getBNodeId()) . '"';
                }

                // mertens patch -3
                if (!$alreadyOutput && $pcount > 0) { // e.g. for vcard:adr and vcard:org
                    $tag .= " rdf:nodeID=\"" . htmlspecialchars($obj->getBNodeId()) . '"';
                }
                //
            } elseif ($pcount == 0) {
                // if we have resource with properties - it will be on its own, we should not put rdf:resource here.
//                if ($rpcount != 1 or $pcount == 0) { //  if ($alreadyOutput or $rpcount != 1 or $pcount == 0) {
                $tag .= " rdf:resource=\"" . htmlspecialchars($obj->getURI()) . '"';
//                }
            }

            if ($pcount > 0) {
                // meertens patch -1
                if ($alreadyOutput) { //I had an infinite loop without $alreadyOutput check.
                //  I had a resource of type Set (former collection), with conceptBaseUri is the same as the set's uri.
                    $uri = htmlspecialchars($obj->getUri());
                    return $tag .= " rdf:resource=\"$uri\"/>\n";
                }

                // meertens patch -2
                if (!$alreadyOutput) {
                    $xml = $this->getResourceXmlString($obj, false, $depth + 1);
                    $this->outputtedResources[$obj->getUri()] = true;
                }///

                if (!empty($xml)) {
                    return "$tag>$xml$indent</$property>\n\n";
                } else {
                    return '';
                }
            } else {
                return $tag . "/>\n";
            }
        } elseif (is_object($obj) and $obj instanceof Literal) {
            $atrributes = "";
            $datatype = $obj->getDatatypeUri();
            if ($datatype) {
                if ($datatype == self::RDF_XML_LITERAL) {
                    $atrributes .= " rdf:parseType=\"Literal\"";
                    $value = strval($obj);
                } else {
                    $datatype = htmlspecialchars($datatype);
                    $atrributes .= " rdf:datatype=\"$datatype\"";
                }
            } elseif ($obj->getLang()) {
                $atrributes .= ' xml:lang="' .
                    htmlspecialchars($obj->getLang()) . '"';
            }
            // Escape the value
            if (!isset($value)) {
                $value = htmlspecialchars(strval($obj));
            }
            return "{$indent}<{$property}{$atrributes}>{$value}</{$property}>\n";
        } else {
            throw new OpenSkosException(
                "Unable to serialise object to xml: " . getType($obj)
            );
        }
    }

    /**
     * @param Resource $res
     * @return string
     */
    protected function determineResType(Resource $res)
    {
        return $res->type();
    }

    /**
     * Determines if $resource should be serialized based on its rdf:type and $options
     * @param type $resource
     * @param type $options
     * @return boolean
     */
    protected function shouldBeSerialized($resource, $options)
    {
        if (empty($options[self::OPTION_RESOURCE_TYPES_TO_SERIALIZE])) {
            return true;
        }

        if ($resource->get('rdf:type') !== null &&
            $resource->get('rdf:type')->getUri() !== null &&
            in_array($resource->get('rdf:type')->getUri(), $options[self::OPTION_RESOURCE_TYPES_TO_SERIALIZE])) {
            return true;
        } else {
            return false;
        }
    }
}
