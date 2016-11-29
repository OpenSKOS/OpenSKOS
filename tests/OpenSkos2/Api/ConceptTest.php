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

class ConceptTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateWithoutXML()
    {
        $request = (new \Zend\Diactoros\ServerRequest());
        $conceptMock = $this->getMockBuilder('\OpenSkos2\ConceptManager')
                ->disableOriginalConstructor()
                ->getMock();
        $resourceMock = $this->getMockBuilder('\OpenSkos2\Rdf\ResourceManager')
                     ->disableOriginalConstructor()
                     ->getMock();
        $autocompleteMock = $this->getMockBuilder('\OpenSkos2\Search\Autocomplete')
                     ->disableOriginalConstructor()
                     ->getMock();
        $concept = new \OpenSkos2\Api\Concept($resourceMock, $conceptMock, $autocompleteMock);
        $response = $concept->create($request);
        
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals((string)$response->getBody(), 'Recieved RDF-XML is not valid XML');
    }
    
    public function testCreateWithoutTenant()
    {
        $request = $this->getRequest();
        $conceptMock = $this->getMockBuilder('\OpenSkos2\ConceptManager')
                            ->disableOriginalConstructor()
                            ->getMock();
        $resourceMock = $this->getMockBuilder('\OpenSkos2\Rdf\ResourceManager')
                             ->disableOriginalConstructor()
                             ->getMock();
        $autocompleteMock = $this->getMockBuilder('\OpenSkos2\Search\Autocomplete')
                                 ->disableOriginalConstructor()
                                 ->getMock();
        $concept = new \OpenSkos2\Api\Concept($resourceMock, $conceptMock, $autocompleteMock);
        $response = $concept->create($request);
        
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals((string)$response->getBody(), 'No tenant specified');
    }
    
    /**
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    private function getRequest()
    {
        $xml = '<rdf:RDF 
            xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" 
            xmlns:openskos="http://openskos.org/xmlns#"
            xmlns:skos="http://www.w3.org/2004/02/skos/core#"
            >
            <rdf:Description rdf:about="http://data.beeldengeluid.nl/gtaa/28586">
              <rdf:type rdf:resource="http://www.w3.org/2004/02/skos/core#Concept"/>
              <skos:prefLabel xml:lang="nl">doodstraf</skos:prefLabel>
              <skos:inScheme rdf:resource="http://data.beeldengeluid.nl/gtaa/Onderwerpen"/>
              <skos:broader rdf:resource="http://data.beeldengeluid.nl/gtaa/24842"/>
              <skos:related rdf:resource="http://data.beeldengeluid.nl/gtaa/25652"/>
              <skos:related rdf:resource="http://data.beeldengeluid.nl/gtaa/24957"/>
              <skos:altLabel xml:lang="nl">kruisigingen</skos:altLabel>
              <skos:broader rdf:resource="http://data.beeldengeluid.nl/gtaa/27731"/>
              <skos:related rdf:resource="http://data.beeldengeluid.nl/gtaa/28109"/>
              <skos:inScheme rdf:resource="http://data.beeldengeluid.nl/gtaa/GTAA"/>
              <skos:notation>28586</skos:notation>
            </rdf:Description>
        </rdf:RDF>';
        $stream = new \Zend\Diactoros\Stream('php://memory', 'wb+');
        $stream->write($xml);
        return (new \Zend\Diactoros\ServerRequest())->withBody($stream);
    }
}
