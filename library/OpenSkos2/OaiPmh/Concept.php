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

namespace OpenSkos2\OaiPmh;

use DateTime;
use DOMDocument;
use OpenSkos2\Concept as SkosConcept;
use OpenSkos2\Namespaces\Dc;
use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Namespaces\Owl;
use OpenSkos2\Namespaces\Rdf;
use OpenSkos2\Namespaces\Rdfs;
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Rdf\Uri;
use Picturae\OaiPmh\Implementation\Record\Header;
use Picturae\OaiPmh\Interfaces\Record;

class Concept implements Record
{
    
    private $concept;
    
    /**
     *
     * @param SkosConcept $concept
     */
    public function __construct(SkosConcept $concept)
    {
        $this->concept = $concept;
    }
    
    /**
     * Get header
     * @return Header
     */
    public function getHeader()
    {
        $concept = $this->concept;
        $datestamp = $concept->getProperty(\OpenSkos2\Namespaces\DcTerms::MODIFIED)[0]->getValue();
        $setSpecs = [];
        $schemes = $concept->getProperty(Skos::INSCHEME);
        foreach ($schemes as $scheme) {
            $setSpecs[] = $scheme->getUri();
        }

        return new Header($concept->geturi(), $datestamp, $setSpecs);
    }

    /**
     * Convert skos concept to \DomDocument to use as metadata in OAI-PMH Interface
     *
     * @return DOMDocument
     */
    public function getMetadata()
    {
        $metadata = new DOMDocument;
        $root = $metadata->createElementNS(Rdf::TYPE, 'rdf:RDF');
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:skos', Skos::NAME_SPACE);
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:dc', Dc::NAME_SPACE);
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:dcterms', DcTerms::NAME_SPACE);
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:openskos', OpenSkos::NAME_SPACE);
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:owl', Owl::NAME_SPACE);
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:rdfs', Rdfs::NAME_SPACE);
        $metadata->appendChild($root);
        
        // All data that will be added to the documet
        $data = [
            Skos::NAME_SPACE => [
                Skos::NOTATION => 'skos:notation',
                Skos::PREFLABEL => 'skos:prefLabel',
                Skos::ALTLABEL => 'skos:altLabel',
                Skos::HIDDENLABEL => 'skos:hiddenLabel',
                Skos::SCOPENOTE => 'skos:scopeNote',
                Skos::INSCHEME => 'skos:inScheme',
            ],
            OpenSkos::NAME_SPACE => [
                OpenSkos::STATUS => 'openskos:status',
                OpenSkos::UUID => 'openskos:uuid',
            ],
            \OpenSkos2\Namespaces\DcTerms::NAME_SPACE => [
                \OpenSkos2\Namespaces\DcTerms::DATEACCEPTED => 'dcterms:dateAccepted',
                \OpenSkos2\Namespaces\DcTerms::DATESUBMITTED => 'dcterms:dateSubmitted',
                \OpenSkos2\Namespaces\DcTerms::MODIFIED => 'dcterms:modified',
            ]
        ];
        
        $concept = $this->concept;
        
        foreach ($data as $mainNamespace => $names) {
            foreach ($names as $namespace => $tag) {
                $properties = $concept->getProperty($namespace);
                foreach ($properties as $property) {
                    if ($property instanceof Uri) {
                        $element = $metadata->createElementNS($mainNamespace, $tag, $property->getUri());
                        
                    } elseif ($property->getValue() instanceof DateTime) {
                        $date = $property->getValue();
                        $element = $metadata->createElementNS($mainNamespace, $tag, $date->format(DATE_W3C));
                        $language = $property->getLanguage();
                        if (!empty($language)) {
                            $element->setAttribute('language', $language);
                        }
                    } else {
                        $element = $metadata->createElementNS(
                            $mainNamespace,
                            $tag,
                            htmlspecialchars($property->getValue(), ENT_XML1)
                        );
                        
                        $language = $property->getLanguage();
                        if (!empty($language)) {
                            $element->setAttribute('language', $language);
                        }
                    }

                    $root->appendChild($element);
                }
            }
        }

        return $metadata;
    }
    
    /**
     * @return DomDocument|null
     */
    public function getAbout()
    {
    }
}
