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
class RdfResponse implements \OpenSkos2\Api\Response\ResponseInterface
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
        $root = $doc->createElementNS(\OpenSkos2\Namespaces\Rdf::TYPE, 'rdf:RDF');
        $ns = 'http://www.w3.org/2000/xmlns/';
        $root->setAttributeNS($ns, 'xmlns:skos', \OpenSkos2\Namespaces\Skos::NAME_SPACE);
        $root->setAttributeNS($ns, 'xmlns:dc', \OpenSkos2\Namespaces\Dc::NAME_SPACE);
        $root->setAttributeNS($ns, 'xmlns:dcterms', \OpenSkos2\Namespaces\DcTerms::NAME_SPACE);
        $root->setAttributeNS($ns, 'xmlns:openskos', \OpenSkos2\Namespaces\OpenSkos::NAME_SPACE);
        $root->setAttributeNS($ns, 'xmlns:owl', \OpenSkos2\Namespaces\Owl::NAME_SPACE);
        $root->setAttributeNS($ns, 'xmlns:rdfs', \OpenSkos2\Namespaces\Rdfs::NAME_SPACE);
        $root->setAttribute('openskos:numFound', $this->result->getTotal());
        $root->setAttribute('openskos:start', $this->result->getStart());
        $doc->appendChild($root);
        
        foreach ($this->result->getConcepts() as $concept) {
            /* @var $concept \OpenSkos2\Concept */
            $desc = $doc->createElement('rdf:Description');
            $desc->setAttribute('rdf:about', $concept->getUri());
            $this->addConcept($desc, $doc, $concept);
            $root->appendChild($desc);
        }
        
        return $doc;
    }
    
    /**
     * Add all concept elements as child to the element given
     *
     * @param \DOMElement $element
     * @param \DOMDocument $doc
     * @param \OpenSkos2\Concept $concept
     */
    private function addConcept(\DOMElement $element, \DOMDocument $doc, \OpenSkos2\Concept $concept)
    {
        $type = $doc->createElement('rdf:type');
        $type->setAttribute('rdf:resource', \OpenSkos2\Concept::TYPE);
        $element->appendChild($type);
        
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
            $properties = $concept->getProperty($ns);
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
