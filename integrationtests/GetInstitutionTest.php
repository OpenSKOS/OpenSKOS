<?php

namespace Tests\OpenSkos2\Integration;

require_once 'AbstractTest.php';

class GetInstitutionTest extends AbstractTest
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
        $xml = '<?xml version="1.0" encoding="utf-8" ?>
<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
         xmlns:openskos="http://openskos.org/xmlns#"
         xmlns:vcard="http://www.w3.org/2006/vcard/ns#">
  <rdf:Description>
    <rdf:type rdf:resource="http://www.w3.org/ns/org#FormalOrganization"/>
    <openskos:enableStatussesSystem rdf:datatype="http://www.w3.org/2001/XMLSchema#bool">true</openskos:enableStatussesSystem>
    <openskos:disableSearchInOtherTenants rdf:datatype="http://www.w3.org/2001/XMLSchema#bool">false</openskos:disableSearchInOtherTenants>
    <vcard:ADR rdf:parseType="Resource">
      <vcard:Country>Netherlands</vcard:Country>
      <vcard:Pcode>5555</vcard:Pcode>
      <vcard:Locality>Amsterdam Centrum</vcard:Locality>
      <vcard:Street>ErgensAchterburgwal</vcard:Street>
    </vcard:ADR>
    <vcard:EMAIL>info@test.nl</vcard:EMAIL>
    <vcard:URL>http://test.nl</vcard:URL>
    <vcard:ORG rdf:parseType="Resource">
      <vcard:orgunit>XXX</vcard:orgunit>
      <vcard:orgname>test-tenant</vcard:orgname>
    </vcard:ORG>
    <openskos:code>test</openskos:code>
  </rdf:Description>
</rdf:RDF>';
        $response = self::create($xml, API_KEY_ADMIN, 'institutions', true);
        if ($response->getStatus() === 201) {
            array_push(self::$createdresourses, self::getAbout($response));
        } else {
            var_dump($response->getBody());
        }
    }

    // delete all created resources
    public static function tearDownAfterClass()
    {
        self::deleteResources(self::$createdresourses, API_KEY_ADMIN, 'institutions');
    }

    public function testAllInstitutions()
    {
        $this->allResources('institutions');
    }

    public function testAllInstitutionsJson()
    {
        $this->allResourcesJson('institutions');
    }

    public function testAllInstitutionsJsonP()
    {
        $this->allResourcesJsonP('institutions');
    }

    public function testAllInstitutionsRDFXML()
    {
        $this->allResourcesRDFXML('institutions');
    }

    public function testAllInstitutionsHTML()
    {
        $this->allResourcesHTML('institutions');
    }

    public function testInstitution()
    {
        if (self::$init['optional.backward_compatible']) {
            $this->resource('institutions', 'example');
        } else {
            $this->resource('institutions', 'test');
        }
    }

    public function testInstitutionJson()
    {
        if (!self::$init['optional.backward_compatible']) {
            $this->resourceJson('institutions', 'test');
        }
        $this->resourceJson('institutions', 'example');
    }

    public function testInstitutionJsonP()
    {
        if (!self::$init['optional.backward_compatible']) {
            $this->resourceJsonP('institutions', 'test');
        }
        $this->resourceJsonP('institutions', 'example');
    }

    public function testInstitutionHTML()
    {
        if (self::$init['optional.backward_compatible']) {
            $this->resourceHTML('institutions', 'example');
        } else {
            $this->resourceHTML('institutions', 'test');
        }
    }

    ////////////////////////////////////
    protected function assertionsJsonResource($institution, $singleResourceCheck)
    {
        switch ($institution["code"]) {
            case "test": {
                    if (self::$init["optional.backward_compatible"]) {
                        $this->assertEquals("info@test.nl", $institution["email"]);
                        $this->assertEquals("test-tenant", $institution["name"]);
                    } else {
                        $this->assertEquals("test-tenant", $institution["vcard_org"]["vcard_orgname"]);
                        $this->assertEquals("info@test.nl", $institution["vcard_email"]);
                    }
                    break;
                }
            case "example": {
                    if (self::$init["optional.backward_compatible"]) {
                        if ($singleResourceCheck) {
                            $this->assertEquals("1", count($institution["collections"]));
                        }
                    } else {
                        if ($singleResourceCheck) {
                            $this->assertEquals("1", count($institution["sets"]));
                        }
                    }
                    break;
                }
            default : {
                    $this->assertEquals(0, 1, "The institution has a code " . $institution["code"] . " which does not belong to the list of test institutions.");
                }
        }
    }

    protected function assertionsJsonResources($institutions)
    {
        if (self::$init['optional.backward_compatible']) {
            $this->assertEquals(1, count($institutions["docs"]));
            $this->assertionsJsonResource($institutions["docs"][0], false);
        } else {
            $this->assertEquals(NUMBER_INSTITUTIONS, count($institutions["docs"]));
            $this->assertionsJsonResource($institutions["docs"][0], false);
            $this->assertionsJsonResource($institutions["docs"][1], false);
        }
    }

    protected function assertionsXMLRDFResource(\Zend_Dom_Query $dom)
    {
        if (self::$init['optional.backward_compatible']) {
            $results1 = $dom->query('openskos:code');
            $results2 = $dom->query('vcard:orgname');
            $results3 = $dom->query('vcard:EMAIL');
            $this->AssertEquals("example", $results1->current()->nodeValue);
            $this->AssertEquals("test tenant", $results2->current()->nodeValue);
            $this->AssertEquals("admin@test.com", $results3->current()->nodeValue);
        } else {
            $results1 = $dom->query('openskos:code');
            $results2 = $dom->query('vcard:orgname');
            $results3 = $dom->query('vcard:EMAIL');
            $this->AssertEquals("test", $results1->current()->nodeValue);
            $this->AssertEquals("test-tenant", $results2->current()->nodeValue);
            $this->AssertEquals("info@test.nl", $results3->current()->nodeValue);
        }
    }

    protected function assertionsXMLRDFResources($response)
    {
        $dom = new \Zend_Dom_Query();
        $dom->setDocumentXML($response->getBody());
        $results1 = $dom->query('rdf:Description');
        if (self::$init['optional.backward_compatible']) {
            $this->assertEquals(1, count($results1));
        } else {
            $this->assertEquals(NUMBER_INSTITUTIONS, count($results1));
        }
        $namespaces = array(
            "vcard" => "http://www.w3.org/2006/vcard/ns#"
        );
        $dom->registerXpathNamespaces($namespaces);
        $this->assertionsXMLRDFResource($dom);
    }

    protected function assertionsHTMLResource(\Zend_Dom_Query $dom, $i)
    {
        $header2 = $dom->query('h2');
        $items = $dom->query('dl > dt');
        $values = $dom->query('dl > dd');
        $formats = $dom->query('ul > li > a');

        $title = $this->getByIndex($header2, $i)->nodeValue;

        if (self::$init['optional.backward_compatible']) {
            $this->AssertEquals('test tenant', $title);
        } else {
            $this->AssertEquals('test-tenant', $title);
        }

        $i = 0;
        $j = 0;
        foreach ($items as $item) {
            if ($item->nodeValue === "code:") {
                if (self::$init['optional.backward_compatible']) {
                    $this->AssertEquals("example", $this->getbyIndex($values, $j)->nodeValue);
                } else {
                    $this->AssertEquals("test", $this->getbyIndex($values, $j)->nodeValue);
                }
                $i++;
            }
            if ($item->nodeValue === "type:") {
                $this->AssertEquals("http://www.w3.org/ns/org#FormalOrganization", $this->getbyIndex($values, $j)->nodeValue);
                $i++;
            }
            $j++;
        }
        $this->AssertEquals(2, $i);
        $this->AssertEquals(3, count($formats));
    }

    protected function assertionsHTMLAllResources($response)
    {
        $dom = new \Zend_Dom_Query();
        $dom->setDocumentHTML($response->getBody());
        $institutions = $dom->query('ul > li > a > strong');

        if (self::$init['optional.backward_compatible']) {
            $this->AssertEquals(1, count($institutions));
            $title = $this->getByIndex($institutions, 0)->nodeValue;
            $this->AssertEquals('test tenant', $title);
        } else {
            $this->AssertEquals(NUMBER_INSTITUTIONS, count($institutions));
            $title = $this->getByIndex($institutions, 1)->nodeValue;
            $this->AssertEquals('test-tenant', $title);
        }
        
        $list = $dom->query('ul > li > a');
        if (self::$init['optional.backward_compatible']) {
            $this->AssertEquals(2+1, count($list)); // 2 for available formats
        } else {
            $this->AssertEquals(2+NUMBER_INSTITUTIONS, count($list)); 
        }
    }

}
