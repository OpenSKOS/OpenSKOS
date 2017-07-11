<?php

namespace Tests\OpenSkos2\Integration;

require_once 'AbstractTest.php';

class GetSetTest extends AbstractTest
{

    public static function setUpBeforeClass()
    {
        self::$init = parse_ini_file(__DIR__ . '/../../../application/configs/application.ini');

        self::$createdresourses = array();

        self::$client = new \Zend_Http_Client();
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
        if (empty(self::$init['options.authorisation'])) {
            echo 'These tests must be run when an authorisation procedure is specified. '
            . 'Now the authroisation is not specified, update application.ini.';
            if ($response->getStatus() !== 501) {
                echo 'Creation of institutions is not allowed when the authorisation is not specified. '
                . 'There is something wrong because it is still created.';
            }
        }
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
        if (empty(self::$init['options.authorisation'])) {
            echo self::$message;
            return;
        }
        $this->allResources('collections');
    }

    public function testAllSetsJson()
    {
        if (empty(self::$init['options.authorisation'])) {
            echo self::$message;
            return;
        }
        $this->allResourcesJson('collections');
    }

    public function testAllSetsJsonP()
    {
        if (empty(self::$init['options.authorisation'])) {
            echo self::$message;
            return;
        }
        $this->allResourcesJsonP('collections');
    }

    public function testAllISetsRDFXML()
    {
        if (empty(self::$init['options.authorisation'])) {
            echo self::$message;
            return;
        }
        $this->allResourcesRDFXML('collections');
    }

    public function testAllSetsHTML()
    {
        if (empty(self::$init['options.authorisation'])) {
            echo self::$message;
            return;
        }
        $this->allResourcesHTML('collections');
    }

    public function testSet()
    {
        if (empty(self::$init['options.authorisation'])) {
            echo self::$message;
            return;
        }
        $this->resource('collections', 'test-set');
    }

    public function testSetJson()
    {
        if (empty(self::$init['options.authorisation'])) {
            echo self::$message;
            return;
        }
        $this->resourceJson('collections', 'test-set');
    }

    public function testSetJsonP()
    {
        if (empty(self::$init['options.authorisation'])) {
            echo self::$message;
            return;
        }
        $this->resourceJsonP('collections', 'test-set');
    }

    public function testSetHTML()
    {
        if (empty(self::$init['options.authorisation'])) {
            echo self::$message;
            return;
        }
        $this->resourceHTML('collections', 'test-set');
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
        $this->assertEquals(NUMBER_SETS, count($sets["docs"]));
        $this->assertionsJsonResource($sets["docs"][0], false);
        $this->assertionsJsonResource($sets["docs"][1], false);
    }

    protected function assertionsXMLRDFResource(\Zend_Dom_Query $dom)
    {
        $results1 = $dom->query('openskos:code');
        $results2 = $dom->query('dcterms:publisher');
        $this->AssertEquals("test-set", $results1->current()->nodeValue);
        $this->AssertEquals(TENANT_URI, $results2->current()->getAttribute('rdf:resource'));
    }

    protected function assertionsXMLRDFResources($response)
    {
        $dom = new \Zend_Dom_Query();
        $dom->setDocumentXML($response->getBody());
        $descriptions = $dom->query('rdf:Description');
        $this->assertEquals(NUMBER_SETS, count($descriptions));
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
        $this->AssertEquals('Test Set', $title);
        $this->AssertEquals(3, count($formats));
    }

    protected function assertionsHTMLAllResources($response)
    {
        $dom = new \Zend_Dom_Query();
        $dom->setDocumentHTML($response->getBody());
        $sets = $dom->query('ul > li > a > strong');
        $this->AssertEquals(NUMBER_SETS, count($sets));
        $title = $this->getByIndex($sets, 1)->nodeValue;
        $this->AssertEquals('Test Set', $title);
        $list = $dom->query('ul > li > a');
        $this->AssertEquals(2, count($list) - NUMBER_SETS);
    }

}
