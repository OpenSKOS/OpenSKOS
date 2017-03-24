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
    <openskos:uuid>'.$uuid.'</openskos:uuid>
  </rdf:Description>
</rdf:RDF>';
    self::create($xml_inst, API_KEY_ADMIN, '/institution', true);
    $response_inst = self::create($xml_inst, API_KEY_ADMIN, '/institution', true);
    if ($response_inst->getStatus() === 201) {
      $this->test_institution = $instURI;
    } else {
      throw new Exception('Creating test institutions hosting a testing set failed: '. $response_inst->getMessage());
    }
    
    
    $xml = '<?xml version="1.0" encoding="UTF-8"?>
<rdf:RDF xmlns:rdf = "http://www.w3.org/1999/02/22-rdf-syntax-ns#"
         xmlns:openskos = "http://openskos.org/xmlns#"
xmlns:dcterms = "http://purl.org/dc/terms/"
xmlns:dcmitype = "http://purl.org/dc/dcmitype#">
    <rdf:Description>
        <openskos:code>test-set</openskos:code>
        <dcterms:title xml:lang="en">Test Set</dcterms:title>
        <dcterms:license rdf:resource="http://creativecommons.org/licenses/by/4.0/"></dcterms:license>
        <dcterms:publisher rdf:resource="'.$this->test_institution.'""></dcterms:publisher>
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
 
  public function testAllSets() {
    $this->allResources('set');
  }

  public function testAllSetsJson() {
    $this->allResourcesJson('set');
  }

  public function testAllSetsJsonP() {
   $this->allResourcesJsonP('set');
  }
  
  public function testAllISetsRDFXML() {
    $this->allResourcesRDFXML('set');
  }

  public function testAllSetsHTML() {
    $this->allResourcesHTML('set');
  }

  public function testSet() {
    $this->resource('set', 'test-set');
  }

  public function testSetJson() {
    $this->resourceJson('set', 'test-set');
  }

  public function testSetJsonP() {
    $this->resourceJsonP('set', 'test-set');
  }

 public function testSetHTML() {
   $this->resourceHTML('set', 'test-set');
  }

  ////////////////////////////////////
  protected function assertionsJsonResource($set) {
    $this->assertEquals("test-set", $set["code"]);
    $this->assertEquals($this->test_institution, $set["dcterms_publisher"]);
  }

  protected function assertionsJsonResources($sets) {
    $this->assertEquals(NUMBER_SETS, count($sets["docs"]));
    $this->assertionsJsonResource($sets["docs"][0]);
  }

  protected function assertionsXMLRDFResource(\Zend_Dom_Query $dom) {
    $results1 = $dom->query('openskos:code');
    $results2 = $dom->query('dcterms:publisher');
    $this->AssertEquals("test-set", $results1->current()->nodeValue);
    $this->AssertEquals($this->test_institution, $results2->current()->nodeValue);
  }

  protected function assertionsXMLRDFResources($response) {
    $dom = new \Zend_Dom_Query();
    $dom->setDocumentXML($response->getBody());
    $results1 = $dom->query('rdf:Description');
    $this->assertEquals(NUMBER_SETS, count($results1));
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
    $this->AssertEquals("test-set", $codeValue);

    $this->AssertEquals(3, count($formats));
  }

  protected function assertionsHTMLAllResources($response) {
    $dom = new \Zend_Dom_Query();
    $dom->setDocumentHTML($response->getBody());
    $sets = $dom->query('ul > li > a > strong'); 
    $this->AssertEquals(NUMBER_SETS, count($sets));
    $title = $this->getByIndex($sets, 1)->nodeValue;
    $this->AssertEquals('Test SEt', $title);
    $list = $dom->query('ul > li > a'); 
    $this->AssertEquals(2, count($list) - NUMBER_SETS);
  }
  
  
}
