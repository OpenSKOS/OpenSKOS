<?php

namespace Tests\OpenSkos2\Integration;

require_once 'AbstractTest.php';

class GetSetTest extends AbstractTest
{

    public static function setUpBeforeClass()
    {
        self::$init = self::getInit();
        self::$createdresourses = array();
        self::$client = new \Zend_Http_Client();

        if (empty(self::$init['optional.authorisation'])) {
            return;
        }

        if (!(self::$init['optional.authorisation'])) {
            return;
        }

        self::$client->setConfig(array(
            'maxredirects' => 0,
            'timeout' => 30));

        self::$client->SetHeaders(array(
            'Accept' => 'text/html,application/xhtml+xml,application/xml',
            'Content-Type' => 'text/xml',
            'Accept-Language' => 'nl,en-US,en',
            'Accept-Encoding' => 'gzip, deflate',
            'Connection' => 'keep-alive')
        );


        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<rdf:RDF xmlns:rdf = "http://www.w3.org/1999/02/22-rdf-syntax-ns#"
         xmlns:openskos = "http://openskos.org/xmlns#"
xmlns:dcterms = "http://purl.org/dc/terms/"
xmlns:dcmitype = "http://purl.org/dc/dcmitype#">
    <rdf:Description>
        <rdf:type rdf:resource="http://purl.org/dc/dcmitype#Dataset"/>
        <openskos:code>test-set</openskos:code>
        <dcterms:title xml:lang="en">Test Set</dcterms:title>
        <dcterms:license rdf:resource="http://creativecommons.org/licenses/by/4.0/"></dcterms:license>
        <dcterms:publisher rdf:resource="' . TENANT_URI . '"></dcterms:publisher>
        <openskos:OAI_baseURL rdf:resource="https://openskos.meertens.knaw.nl/api/ergens"/>
        <openskos:allow_oai>true</openskos:allow_oai>
        <openskos:conceptBaseUri>http://example.com/set-example</openskos:conceptBaseUri>
        <openskos:webpage rdf:resource="http://ergens"/>
    </rdf:Description>
  </rdf:RDF>';
        $response = self::create($xml, API_KEY_ADMIN, 'collections', true);
        if ($response->getStatus() === 201) {
            array_push(self::$createdresourses, self::getAbout($response));
        } else {
            var_dump($xml);
            var_dump($response->getMessage());
            var_dump($response->getBody());
        }
    }

    // delete all created resources
    public static function tearDownAfterClass()
    {
        self::deleteResources(self::$createdresourses, API_KEY_ADMIN, 'collections');
    }

    public function testAllSets()
    {
        $this->allResources('collections');
    }

    public function testAllSetsJson()
    {
        $this->allResourcesJson('collections');
    }

    public function testAllSetsJsonP()
    {
        $this->allResourcesJsonP('collections');
    }

    public function testAllISetsRDFXML()
    {
        $this->allResourcesRDFXML('collections');
    }

    public function testAllSetsHTML()
    {
        $this->allResourcesHTML('collections');
    }

    public function testSet()
    {
        if (self::$init['optional.backward_compatible']) {
            $this->resource('collections', 'Test Set 01');
        } else {
            $this->resource('set', 'test-set');
        }
    }

    public function testSetJson()
    {
        if (self::$init['optional.backward_compatible']) {
            $this->resourceJson('collections', 'Test Set 01');
        } else {
            $this->resourceJson('set', 'test-set');
        }
    }

    public function testSetJsonP()
    {
        if (self::$init['optional.backward_compatible']) {
            $this->resourceJsonP('collections', 'Test Set 01');
        } else {
            $this->resourceJsonP('set', 'test-set');
        }
    }

    public function testSetHTML()
    {
        if (self::$init['optional.backward_compatible']) {
            $this->resourceHTML('collections', 'Test Set 01');
        } else {
            $this->resourceHTML('set', 'test-set');
        }
    }

    ////////////////////////////////////
    protected function assertionsJsonResource($set, $isSingleResourceCheck)
    {
        switch ($set["code"]) {
            case "test-set": {
                    $this->assertEquals('http://ergens', $set["webpage"]);
                    $this->assertEquals('Test Set', $set["dcterms_title@en"]);
                    $this->assertEquals(TENANT_URI, $set["dcterms_publisher"]);
                    break;
                }
            case "set01": {
                    $this->assertEquals(SET_PAGE, $set["webpage"]);
                    $this->assertEquals(SET_UUID, $set["uuid"]);
                    $this->assertEquals(TENANT_URI, $set["dcterms_publisher"]);
                    break;
                }
            default : {
                    $this->assertEquals(0, 1, "The set has a code " . $set["code"] . " which does not belong to the list of test sets.");
                }
        }
    }

    protected function assertionsJsonResources($sets)
    {
        if (self::$init['optional.backward_compatible']) {
            $this->assertEquals(1, count($sets["docs"]));
            $this->assertionsJsonResource($sets["docs"][0], false);
        } else {
            $this->assertEquals(NUMBER_SETS, count($sets["docs"]));
            $this->assertionsJsonResource($sets["docs"][0], false);
            $this->assertionsJsonResource($sets["docs"][1], false);
        }
    }

    protected function assertionsXMLRDFResource(\Zend_Dom_Query $dom)
    {
        $results1 = $dom->query('openskos:code');
        $results2 = $dom->query('dcterms:publisher');
        if (self::$init['optional.backward_compatible']) {
            $this->AssertEquals("Test Set 01", $results1->current()->nodeValue);
        } else {
            $this->AssertEquals("test-set", $results1->current()->nodeValue);
        }
        $this->AssertEquals(TENANT_URI, $results2->current()->getAttribute('rdf:resource'));
    }

    protected function assertionsXMLRDFResources($response)
    {
        $dom = new \Zend_Dom_Query();
        $dom->setDocumentXML($response->getBody());
        $descriptions = $dom->query('rdf:Description');
        if (self::$init['optional.backward_compatible']) {
            $this->assertEquals(1, count($descriptions));
        } else {
            $this->assertEquals(NUMBER_SETS, count($descriptions));
        }
        foreach ($descriptions as $description) {
            $code = $description->getElementsByTagName('code')->item(0)->nodeValue;
            $publisher = $description->getElementsByTagName('publisher')->item(0)->getAttribute('rdf:resource');
            $page = $description->getElementsByTagName('webpage')->item(0)->getAttribute('rdf:resource');
            switch ($code) {
                case "test-set": {
                        $this->assertEquals(TENANT_URI, $publisher);
                        $this->assertEquals('http://ergens', $page);
                        break;
                    }
                case "set01": {
                        $this->assertEquals(TENANT_URI, $publisher);
                        $this->assertEquals(SET_PAGE, $page);
                        $uuid = $description->getElementsByTagName('uuid')->item(0)->nodeValue;
                        $this->assertEquals(SET_UUID, $uuid);
                        break;
                    }
                default : {
                        $this->assertEquals(0, 1, "The set has a code " . $code . " which does not belong to the list of test sets.");
                    }
            }
        }
    }

    protected function assertionsHTMLResource(\Zend_Dom_Query $dom, $i)
    {
        $header2 = $dom->query('h2');
        $formats = $dom->query('ul > li > a');

        $title = $this->getByIndex($header2, $i)->nodeValue;
        if (self::$init['optional.backward_compatible']) {
            $this->AssertEquals('Test Set 01', $title);
        } else {
            $this->AssertEquals('Test Set', $title);
        }
        $this->AssertEquals(3, count($formats));
    }

    protected function assertionsHTMLAllResources($response)
    {
        $dom = new \Zend_Dom_Query();
        $dom->setDocumentHTML($response->getBody());
        $sets = $dom->query('ul > li > a > strong');
        $this->AssertEquals(NUMBER_SETS, count($sets));
        $title = $this->getByIndex($sets, 1)->nodeValue;
        if (self::$init['optional.backward_compatible']) {
            $this->AssertEquals('Test Set 01', $title);
        } else {
            $this->AssertEquals('Test Set', $title);
        }
        $list = $dom->query('ul > li > a');
        
        $this->AssertEquals(2, count($list) - NUMBER_SETS);
    }

}
