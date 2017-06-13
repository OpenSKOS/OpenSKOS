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
class RdfResponse extends ResultSetResponse
{

    /**
     * Get response
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getResponse()
    {
        $stream = new \Zend\Diactoros\Stream('php://memory', 'wb+');
        $stream->write($this->getXML()->saveXML());
        $response = (new \Zend\Diactoros\Response())
            ->withBody($stream)
            ->withHeader('Content-Type', 'text/xml; charset=UTF-8');
        return $response;
    }

    /**
     * Build RDF Document
     *
     * @return \DOMDocument
     */
    private function getXML()
    {
        $doc = new \DOMDocument;
        $root = $doc->createElementNS(\OpenSkos2\Namespaces\Rdf::NAME_SPACE, 'rdf:RDF');
        $ns = 'http://www.w3.org/2000/xmlns/';
        // @TODO This namespaces are basically for concept. Now there are more resources.
        foreach (\OpenSkos2\Namespaces::getRdfConceptNamespaces() as $prefix => $namespace) {
            $root->setAttributeNS($ns, 'xmlns:' . $prefix, $namespace);
        }
        $root->setAttribute('openskos:numFound', $this->result->getTotal());
        $root->setAttribute('openskos:rows', $this->result->getLimit());
        $root->setAttribute('openskos:start', $this->result->getStart());
        $doc->appendChild($root);
        foreach ($this->result->getResources() as $resource) {
            // @TODO This can be replaced with something like the OpenSkos2\Export\Serialiser\Format\Xml().
            // or both of them with something shared.
            /* @var $resource \OpenSkos2\Rdf\Resource */
            $xml = (new \OpenSkos2\Api\Transform\DataRdf(
                $resource, true, $this->propertiesList, $this->excludePropertiesList
                ))->transform();
            
            $resourceXML = new \DOMDocument();
            $resourceXML->loadXML($xml);

            // Rename rdf:RDF to rdf:Description
            $desc = $resourceXML->createElement('rdf:Description');
            $desc->setAttribute('rdf:about', $resource->getUri());
            $this->renameElement($resourceXML->documentElement, $desc);

            $this->moveNodesFromResource($resourceXML->documentElement);

            $root->appendChild(
                $doc->importNode($resourceXML->documentElement, true)
            );
           
        }
        return $doc;
    }

    /**
     * Move nodes from node skos:Resource to node root rdf:Description
     * to stay backwards compatible with the old API
     */
    private function moveNodesFromResource(\DOMElement $resource)
    {
        $skosResource = $resource->childNodes->item(1);

        if (empty($skosResource->childNodes)) {
            return;
        }

        foreach ($skosResource->childNodes as $child) {
            $skosResource->parentNode->appendChild($child->cloneNode(true));
        }
        $resource->removeChild($skosResource);
    }

    /**
     * Renames a node in a DOM Document, both elements must come from the same document.
     *
     * @param \DOMElement $node
     * @param \DOMELement $renamed
     * @return DOMNode
     */
    private function renameElement(\DOMElement $node, \DOMELement $renamed)
    {
        foreach ($node->attributes as $attribute) {
            $renamed->setAttribute($attribute->nodeName, $attribute->nodeValue);
        }
        while ($node->firstChild) {
            $renamed->appendChild($node->firstChild);
        }
        return $node->parentNode->replaceChild($renamed, $node);
    }

    /**
     * Add all resource elements as child to the element given
     *
     * @param \DOMElement $element
     * @param \DOMDocument $doc
     * @param \OpenSkos2\Rdf\Resource $resource
     */
    private function addResource(\DOMElement $element, \DOMDocument $doc, \OpenSkos2\Rdf\Resource $resource)
    {
        $type = $doc->createElement('rdf:type');
        $type->setAttribute('rdf:resource', $resource->getType());
        $element->appendChild($type);
        // @TODO This is map strictly for resources. We have other resources as well now.
        $map = [
            'openskos:status' => \OpenSkos2\Namespaces\OpenSkos::STATUS,
            'skos:notation' => \OpenSkos2\Namespaces\Skos::NOTATION,
            'skos:broadMatch' => \OpenSkos2\Namespaces\Skos::BROADMATCH,
            'skos:related' => \OpenSkos2\Namespaces\Skos::RELATED,
            'skos:historyNote' => \OpenSkos2\Namespaces\Skos::HISTORYNOTE,
            'skos:prefLabel' => \OpenSkos2\Namespaces\Skos::PREFLABEL,
            'skos:altLabel' => \OpenSkos2\Namespaces\Skos::ALTLABEL,
            'skos:inScheme' => \OpenSkos2\Namespaces\Skos::INSCHEME,
            'skos:topConceptOf' => \OpenSkos2\Namespaces\Skos::TOPCONCEPTOF,
            'dcterms:modified' => \OpenSkos2\Namespaces\DcTerms::MODIFIED,
            'dcterms:creator' => \OpenSkos2\Namespaces\DcTerms::CREATOR,
            'dcterms:dateSubmitted' => \OpenSkos2\Namespaces\DcTerms::DATESUBMITTED,
        ];
        foreach ($map as $tag => $ns) {
            $properties = $resource->getProperty($ns);
            foreach ($properties as $prop) {
                if ($prop instanceof \OpenSkos2\Rdf\Uri) {
                    $val = $prop->getUri();
                    $el = $doc->createElement($tag);
                    $el->setAttribute('rdf:resource', $val);
                    $element->appendChild($el);
                    continue;
                }
                $val = $prop->getValue();
                if (empty($val)) {
                    continue;
                }
                if ($val instanceof \DateTime) {
                    $val = $val->format(DATE_W3C);
                    $el = $doc->createElement($tag, $val);
                    $element->appendChild($el);
                    continue;
                }
                $el = $doc->createElement($tag, $val);
                $lang = $prop->getLanguage();
                if (!empty($lang)) {
                    $el->setAttribute('xml:lang', $lang);
                }
                $element->appendChild($el);
            }
        }
    }
}
