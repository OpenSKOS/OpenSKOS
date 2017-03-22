<?php

namespace Tests\OpenSkos2\Integration;

require_once 'AbstractTest.php';

class GetInstitutionTest extends AbstractTest {

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
    $xml = '<?xml version="1.0" encoding="utf-8" ?>
<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
         xmlns:openskos="http://openskos.org/xmlns#"
         xmlns:vcard="http://www.w3.org/2006/vcard/ns#">
  <rdf:Description>
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
    self::create($xml, API_KEY_ADMIN, '/institution', true);
  }

  // delete all created concepts
  public static function tearDownAfterClass() {
    self::deleteConcepts(self::$createdresources, API_KEY_ADMIN, '/institution');
  }

  public function testAllInstitutions() {
    $this->testAllResources('institution');
  }

  public function testAllInstitutionsJson() {
    $this->testAllInstitutionsResourcesJson('institution');
  }

  public function testAllInstitutionsJsonP() {
   $this->testAllInstitutionsResourcesJsonP('institution');
  }

  public function testAllInstitutionsRDFXML() {
    $this->testAllResourcesRDFXML('institution');
  }

  public function testAllInstitutionsHTML() {
    $this->testAllResourcesHTML('institution');
  }

  public function testInstitution() {
    print "\n Test: get a institution in default format ... ";
    $response = $this->getInstitution(API_BASE_URI . '/institution/test', 'text/xml');
    $this->AssertEquals(200, $response->getStatus(), $response->getMessage());
    $dom = new \Zend_Dom_Query();
    $dom->setDocumentXML($response->getBody());
    $this->assertionsXMLRDFInstitution($dom, 0);
  }

  public function testInstitutionJson() {
    print "\n Test: get a institution in json ... ";
    $response = $this->getInstitution(API_BASE_URI . '/institution/test.json', 'application/json');
    $this->AssertEquals(200, $response->getStatus(), $response->getMessage());
    $json = $response->getBody();
    $institution = json_decode($json, true);
    $this->assertionsJsonInstitution($institution, 0);
  }

  public function testInstitutionJsonP() {
    print "\n Test: get a institution in jsonp ... ";
    $response = $this->getInstitution(API_BASE_URI . 'institution/test.jsonp?callback=' . CALLBACK_NAME, 'application/json');
    $this->AssertEquals(200, $response->getStatus(), $response->getMessage());
    $json = $response->getBody();
    $institution = $this->jsonP_decode_parameters($json, CALLBACK_NAME);
    $this->assertionsJsonInstitution($institution, 0);
  }

  public function testInstitutionHTML() {
    print "\n Test: get a institution in html ... ";
    $response = $this->getInstitution(API_BASE_URI . 'institution/test.html', 'text/html');
    $this->AssertEquals(200, $response->getStatus(), $response->getMessage());
    $dom = new \Zend_Dom_Query();
    $dom->setDocumentHTML($response->getBody());
    $this->assertionsHTMLInstitution($dom, 0);
  }

  ////////////////////////////////////
  protected function assertionsJsonInstitution($institution) {
    $this->assertEquals("test", $institution["code"]);
    $this->assertEquals("test-tenant", $institution["vcard_org"]["vcard_orgname"]);
    $this->assertEquals("info@test.nl", $institution["vcard_email"]);
  }

  protected function assertionsJsonInstitutions($institutions) {
    $this->assertEquals(NUMBER_TEST_TENANTS, count($institutions["docs"]));
    $this->assertionsJsonInstitution($institutions["docs"][0]);
  }

  protected function assertionsXMLRDFInstitution(\Zend_Dom_Query $dom) {
    $results1 = $dom->query('vcode');
    $results2 = $dom->query('vcard:orgname');
    $results3 = $dom->query('v:email');
    $this->AssertEquals("test", $results1->current()->nodeValue);
    $this->AssertEquals("test-tenant", $results2->current()->nodeValue);
    $this->AssertEquals("info@test.nl", $results3->current()->getAttribute('rdf:about'));
  }

  protected function assertionsXMLRDFInstitutions($response) {
    $dom = new \Zend_Dom_Query();
    $dom->setDocumentXML($response->getBody());
    $results1 = $dom->query('rdf:Description');
    $this->assertEquals(NUMBER_INSTITUTIONS, count($results1));
    $this->assertionsXMLRDFInstitution($dom);
  }

  protected function assertionsHTMLInstitution(\Zend_Dom_Query $dom, $i) {
    $header2 = $dom->query('h2');
    $codeQuery = $dom->query('dl > dt');
    $codeValueQuery = $dom->query('dl > dd');
    $collectionsQuery = $dom->query('ul > li > p');
    $formats = $dom->query('ul > li > a');

    $title = RequestResponse::getByIndex($header2, $i)->nodeValue;
    $this->AssertEquals(INSTITUTION_NAME, $title);

    $codeItem = RequestResponse::getByIndex($codeQuery, $i)->nodeValue;
    $this->AssertEquals("code:", $codeItem);

    $codeValue = RequestResponse::getByIndex($codeValueQuery, $i)->nodeValue;
    $this->AssertEquals(TENANT, $codeValue);

    $this->AssertEquals(NUMBER_COLLECTIONS, count($collectionsQuery));
    $this->AssertEquals(3, count($formats));
  }

  protected function assertionsHTMLAllInstitutions($response) {
    $dom = new \Zend_Dom_Query();
    $dom->setDocumentHTML($response->getBody());
    $institutions = $dom->query('ul > li > a > strong'); // fetches institutions and formats together
    $this->AssertEquals(NUMBER_INSTITUTIONS, count($institutions));
    for ($i = 0; $i < NUMBER_INSTITUTIONS; $i++) {
      $title = RequestResponse::getByIndex($institutions, $i)->nodeValue;
      if ($i === 0) {
        $this->AssertEquals(INSTITUTION_NAME, $title);
      } else {
        $this->AssertEquals(1, 0);
      }
    }

    $list = $dom->query('ul > li > a'); // fetches institutions and formats together
    $this->AssertEquals(3, count($list) - NUMBER_INSTITUTIONS);
  }

  protected function getInstitution($requestString, $contentType) {
    print "\n $requestString \n";
    self::$client->resetParameters();

    self::$client->setUri($requestString);
    self::$client->setConfig(array(
      'maxredirects' => 0,
      'timeout' => 30));

    self::$client->SetHeaders(array(
      'Accept' => 'text/html,application/xhtml+xml,application/xml',
      'Content-Type' => $contentType,
      'Accept-Language' => 'nl,en-US,en',
      'Accept-Encoding' => 'gzip, deflate',
      'Connection' => 'keep-alive')
    );
    echo $requestString;
    $response = self::$client->request(\Zend_Http_Client::GET);

    return $response;
  }

}
