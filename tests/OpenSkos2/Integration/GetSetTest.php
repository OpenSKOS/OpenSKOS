<?php

namespace Tests\OpenSkos2\Integration;

require_once 'AbstractTest.php';

class GetInstitutionTest extends AbstractTest {
  
  private $test_institution;

  public static function setUpBeforeClass() {
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
    
    $uuid = uniqid();
    $instURI = API_BASE_URI . "/institution/". $uuid;
    $xml_inst = '<?xml version="1.0" encoding="utf-8" ?>
<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
         xmlns:openskos="http://openskos.org/xmlns#"
         xmlns:vcard="http://www.w3.org/2006/vcard/ns#">
  <rdf:Description rdf:about="'.$instURI.'">
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
    self::create($xml_inst, API_KEY_ADMIN, '/institution', true);
    $response_inst = self::create($xml_inst, API_KEY_ADMIN, '/institution', true);
    if ($response_inst->getStatus() === 201) {
      $this->test_institution = $instURI;
    } else {
      throw new Exception('Creating test institutions hosting a testing set failed: '. $response->getMessage());
    }
    
    
    $xml = '<?xml version="1.0" encoding="UTF-8"?>
<rdf:RDF xmlns:rdf = "http://www.w3.org/1999/02/22-rdf-syntax-ns#"
         xmlns:openskos = "http://openskos.org/xmlns#"
xmlns:dcterms = "http://purl.org/dc/terms/"
xmlns:dcmitype = "http://purl.org/dc/dcmitype#">
    <rdf:Description>
        <openskos:code>set-test</openskos:code>
        <dcterms:title xml:lang="en">Set Test</dcterms:title>
        <dcterms:license rdf:resource="http://creativecommons.org/licenses/by/4.0/"></dcterms:license>
        <dcterms:publisher rdf:resource="http://mertens/knaw/formalorganization_2b46e792-a4e9-4edb-821c-d2cc78e3f1bf"></dcterms:publisher>
        <openskos:OAI_baseURL rdf:resource="https://openskos.meertens.knaw.nl/api/ergens"/>
        <openskos:allow_oai>true</openskos:allow_oai>
        <openskos:conceptBaseUri>http://example.com/set-example</openskos:conceptBaseUri>
        <openskos:webpage rdf:resource="http://ergens"/>
    </rdf:Description>
</rdf:RDF>';
    self::create($xml, API_KEY_ADMIN, '/set', true);
    $response = self::create($xml, API_KEY_ADMIN, '/set', true);
    if ($response->getStatus() === 201) {
      array_push(self::$createdresources, $this->getAbout($response));
    }
  }

  // delete all created resources
  public static function tearDownAfterClass() {
    self::deleteResources(self::$createdresources, API_KEY_ADMIN, '/set');
    self::delete($this->test_institution, API_KEY_ADMIN, '/institution');
  }
 
  public function testAllInstitutions() {
    $this->allResources('institution');
  }

  public function testAllInstitutionsJson() {
    $this->allResourcesJson('institution');
  }
/*
  public function testAllInstitutionsJsonP() {
   $this->allResourcesJsonP('institution');
  }
*/
  public function testAllInstitutionsRDFXML() {
    $this->allResourcesRDFXML('institution');
  }

  public function testAllInstitutionsHTML() {
    $this->allResourcesHTML('institution');
  }

  public function testInstitution() {
    $this->resource('institution', 'test');
  }

  public function testInstitutionJson() {
    $this->resourceJson('institution', 'test');
  }

  public function testInstitutionJsonP() {
    $this->resourceJsonP('institution', 'test');
  }

 public function testInstitutionHTML() {
   $this->resourceHTML('institution', 'test');
  }

  ////////////////////////////////////
  protected function assertionsJsonResource($institution) {
    $this->assertEquals("test", $institution["code"]);
    $this->assertEquals("test-tenant", $institution["vcard_org"]["vcard_orgname"]);
    $this->assertEquals("info@test.nl", $institution["vcard_email"]);
  }

  protected function assertionsJsonResources($institutions) {
    $this->assertEquals(NUMBER_INSTITUTIONS, count($institutions["docs"]));
    $this->assertionsJsonResource($institutions["docs"][0]);
  }

  protected function assertionsXMLRDFResource(\Zend_Dom_Query $dom) {
    $results1 = $dom->query('openskos:code');
    $results2 = $dom->query('vcard:orgname');
    $results3 = $dom->query('vcard:EMAIL');
    $this->AssertEquals("test", $results1->current()->nodeValue);
    $this->AssertEquals("test-tenant", $results2->current()->nodeValue);
    $this->AssertEquals("info@test.nl", $results3->current()->nodeValue);
  }

  protected function assertionsXMLRDFResources($response) {
    $dom = new \Zend_Dom_Query();
    $dom->setDocumentXML($response->getBody());
    $results1 = $dom->query('rdf:Description');
    $this->assertEquals(NUMBER_INSTITUTIONS, count($results1));
    $namespaces = array(
            "vcard" => "http://www.w3.org/2006/vcard/ns#" 
        );
    $dom->registerXpathNamespaces($namespaces);
    $this->assertionsXMLRDFResource($dom);
  }

  protected function assertionsHTMLResource(\Zend_Dom_Query $dom, $i) {
    $header2 = $dom->query('h2');
    $codeQuery = $dom->query('dl > dt');
    $codeValueQuery = $dom->query('dl > dd');
    $formats = $dom->query('ul > li > a');

    $title = $this->getByIndex($header2, $i)->nodeValue;
    $this->AssertEquals('test-tenant', $title);

    $codeItem = $this->getByIndex($codeQuery, count($codeQuery)-2)->nodeValue;
    $this->AssertEquals("code:", $codeItem);

    $codeValue = $this->getByIndex($codeValueQuery, count($codeQuery)-2)->nodeValue;
    $this->AssertEquals("test", $codeValue);

    $this->AssertEquals(3, count($formats));
  }

  protected function assertionsHTMLAllResources($response) {
    $dom = new \Zend_Dom_Query();
    $dom->setDocumentHTML($response->getBody());
    $institutions = $dom->query('ul > li > a > strong'); // fetches institutions and formats together
    $this->AssertEquals(NUMBER_INSTITUTIONS, count($institutions));
    $title = $this->getByIndex($institutions, 1)->nodeValue;
    $this->AssertEquals('test-tenant', $title);
    $list = $dom->query('ul > li > a'); // fetches institutions and formats together
    $this->AssertEquals(2, count($list) - NUMBER_INSTITUTIONS);
  }
  
  
}
