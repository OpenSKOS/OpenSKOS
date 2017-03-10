<?php

namespace Tests\OpenSkos2\Integration;

require_once __DIR__ . '/Utils.php';

class CreateConceptTest extends \PHPUnit_Framework_TestCase {

  private $client;
  private $createdconcepts;

  protected function setUp() {
    $this->client = new \Zend_Http_Client();
    $this->client->setConfig(array(
      'maxredirects' => 0,
      'timeout' => 30));

    $this->client->SetHeaders(array(
      'Accept' => 'text/html,application/xhtml+xml,application/xml',
      'Content-Type' => 'text/xml',
      'Accept-Language' => 'nl,en-US,en',
      'Accept-Encoding' => 'gzip, deflate',
      'Connection' => 'keep-alive')
    );

    $this->createdconcepts = array();
  }

  protected function tearDown() {
    $this->deleteConcepts($this->createdconcepts);
    unset($this->createdconcepts);
  }

  public function test01CreateConceptWithoutURIWithDateAccepted2() {
//CreateConceptTest::test01CreateConceptWithoutURIWithDateAccepted();
// Create new concept with dateAccepted filled (implicit status APPROVED). This should not be possible. 
    print "\n\n test01 ... \n";
    $randomn = rand(0, 2048);
    $prefLabel = 'testPrefLable_' . $randomn;
    $dateAccepted = '2015-10-02T10:31:35Z';

    $xml = '<rdf:RDF xmlns:dcterms="http://purl.org/dc/terms/" xmlns:ns0="http://dublincore.org/documents/dcmi-terms/#" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:skos="http://www.w3.org/2004/02/skos/core#">' .
      '<rdf:Description>' .
      '<rdf:type rdf:resource="http://www.w3.org/2004/02/skos/core#Concept"/>' .
      '<skos:prefLabel xml:lang="nl">' . $prefLabel . '</skos:prefLabel>' .
      '<dcterms:dateAccepted>' . $dateAccepted . '</dcterms:dateAccepted>' .
      '<skos:inScheme  rdf:resource="' . SCHEMA_URI_1 . '"/>' .
      '</rdf:Description>' .
      '</rdf:RDF>';

    $response = $this->create($xml);
    if ($response->getStatus() === 201) {
      array_push($this->createdconcepts, Utils::getAbout($response));
    }
    $this->AssertEquals(201, $response->getStatus());
  }

  public function test02CreateConceptWithoutUriWithoutDateAccepted() {
// Create a concept without Uri and without dateAccepted , but with UniquePrefLabel. Check XML response.
    print "\n\n test02 ... \n";
    $randomn = rand(0, 2048);
    $prefLabel = 'testPrefLable_' . $randomn;

    $xml = '<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:skos="http://www.w3.org/2004/02/skos/core#" xmlns:openskos="http://openskos.org/xmlns#" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmi="http://dublincore.org/documents/dcmi-terms/#">' .
      '<rdf:Description>' .
      '<rdf:type rdf:resource="http://www.w3.org/2004/02/skos/core#Concept"/>' .
      '<skos:prefLabel xml:lang="nl">' . $prefLabel . '</skos:prefLabel>' .
      '<skos:inScheme  rdf:resource="' . SCHEMA_URI_1 . '"/>' .
      '</rdf:Description>' .
      '</rdf:RDF>';

    $response = $this->create($xml);
    $this->AssertEquals(201, $response->getStatus());
    $this->CheckCreatedConcept($response);
  }

  public function test03CreateConceptWithURIAlreadyExists() {
// test if creating a new concept with an URI that already exists, fails
    print "\n\n test03 ... \n";
    $randomn = rand(0, 2048);
    $prefLabel = 'testPrefLable_' . $randomn;
    $notation = 'notation_' . $randomn;
    $conceptURI = API_BASE_URI . "/" . SET_CODE . "/" . $notation;
    $uuid = uniqid();
    $xml = '<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:openskos="http://openskos.org/xmlns#" xmlns:skos="http://www.w3.org/2004/02/skos/core#" xmlns:dcterms="http://purl.org/dc/terms/" > ' .
      '<rdf:Description rdf:about="' . $conceptURI . '">' .
      '<skos:prefLabel xml:lang="nl">' . $prefLabel . '</skos:prefLabel>' .
      '<skos:notation>' . $notation . '</skos:notation>' .
      '<skos:inScheme  rdf:resource="' . SCHEMA_URI_1 . '"/>' .
      '<openskos:uuid>' . $uuid . '</openskos:uuid>' .
      '</rdf:Description>' .
      '</rdf:RDF>';

// create the first concept with which we will compare
    $response = $this->create($xml, false);
    if ($response->getStatus() === 201) {
      array_push($this->createdconcepts, Utils::getAbout($response));
      $xml2 = str_replace('testPrefLable_', '_another_testPrefLable_', $xml);
      $response2 = $this->create($xml2, "false");
      if ($response2->getStatus() == 201) {
        array_push($this->createdconcepts, Utils::getAbout($response2));
      }
      $this->AssertEquals(400, $response2->getStatus());
    } else {
      throw (new \Exception('Fialure while creating the first concept. Status: ' . $response->getStatus() . "\n " . $response->getMessage()));
    }
  }

  public function test04CreateConceptWithoutURIUniquePrefLabelNoApiKey() {
// create concept without URI. but with unique prefLabel. Api Key is missng.
    print "\n\n test04 ... \n";
    $randomn = rand(0, 2048);
    $prefLabel = 'testPrefLable_' . $randomn;
    $xml = '<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:skos="http://www.w3.org/2004/02/skos/core#" xmlns:openskos="http://openskos.org/xmlns#" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmi="http://dublincore.org/documents/dcmi-terms/#">' .
      '<rdf:Description>' .
      '<rdf:type rdf:resource="http://www.w3.org/2004/02/skos/core#Concept"/>' .
      '<skos:prefLabel xml:lang="nl">' . $prefLabel . '</skos:prefLabel>' .
      '<skos:inScheme  rdf:resource="' . SCHEMA_URI_1 . '"/>' .
      '</rdf:Description>' .
      '</rdf:RDF>';

    $this->client->resetParameters();
    $this->client->setUri(API_BASE_URI . "/concept?");

    $response = $this->client
      ->setEncType('text/xml')
      ->setRawData($xml)
      ->setParameterGet('tenant', TENANT)
      ->setParameterGet('autoGenerateIdentifiers', true)
      ->request('POST');

    if ($response->getStatus() == 201) {
      array_push($this->createdconcepts, Utils::getAbout($response));
    }
    $this->AssertEquals(412, $response->getStatus());
  }

  public function test05CreateConceptWithURIUniquePrefLabel() {
// Create concept with URI and with unique prefLabel, including skos:notation
    print "\n\n test05 ... \n";
    $randomn = rand(0, 4096);
    $prefLabel = 'testPrefLable_' . $randomn;
    $notation = 'testNotation_' . $randomn;
    $about = API_BASE_URI . "/" . SET_CODE . '/' . $notation;
    $uuid = uniqid();
    $xml = '<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:skos="http://www.w3.org/2004/02/skos/core#" xmlns:openskos="http://openskos.org/xmlns#" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmi="http://dublincore.org/documents/dcmi-terms/#" > ' .
      '<rdf:Description rdf:about="' . $about . '">' .
      '<rdf:type rdf:resource="http://www.w3.org/2004/02/skos/core#Concept"/>' .
      '<skos:prefLabel xml:lang="nl">' . $prefLabel . '</skos:prefLabel>' .
      '<skos:notation>' . $notation . '</skos:notation>' .
      '<skos:inScheme  rdf:resource="' . SCHEMA_URI_1 . '"/>' .
      '<openskos:uuid>' . $uuid . '</openskos:uuid>' .
      '</rdf:Description>' .
      '</rdf:RDF>';

    $response = $this->create($xml, false);
    $this->AssertEquals(201, $response->getStatus());
    $this->CheckCreatedConcept($response);
  }

  public function test05BCreateConceptWithURIUniquePrefLabelNoNotation() {
// Create concept with URI and with unique prefLabel, without skos:notation
    print "\n\n test05B ... \n";
    $randomn = rand(0, 4096);
    $prefLabel = 'testPrefLable_' . $randomn;
    $notation = 'testNotation_' . $randomn;
    $about = API_BASE_URI . "/" . SET_CODE . '/' . $notation;
    $uuid = uniqid();
    $xml = '<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:skos="http://www.w3.org/2004/02/skos/core#" xmlns:openskos="http://openskos.org/xmlns#" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmi="http://dublincore.org/documents/dcmi-terms/#" > ' .
      '<rdf:Description rdf:about="' . $about . '">' .
      '<rdf:type rdf:resource="http://www.w3.org/2004/02/skos/core#Concept"/>' .
      '<skos:prefLabel xml:lang="nl">' . $prefLabel . '</skos:prefLabel>' .
      '<skos:inScheme  rdf:resource="' . SCHEMA_URI_1 . '"/>' .
      '<openskos:uuid>' . $uuid . '</openskos:uuid>' .
      '</rdf:Description>' .
      '</rdf:RDF>';

//var_dump($xml);
    $response = $this->create($xml, false);
    if ($response->getStatus() == 201) {
      array_push($this->createdconcepts, $about);
    }
    $this->AssertEquals(400, $response->getStatus(), "This test fails becauseBeG instists that skos:notation is compulsory. In general there may be zero notations, or more than one natotation ");
    var_dump($response->getBody());
  }

  public function test05CCreateConceptWithURIUniquePrefLabel() {
// Create concept with URI and with unique prefLabel, with duplicate skos:notation
    print "\n\n test05C ... \n";
    $randomn = rand(0, 4096);
    $prefLabel = 'testPrefLable_' . $randomn;
    $notation = 'testNotation_' . $randomn;
    $about = API_BASE_URI . "/" . SET_CODE . '/' . $notation;
    $anotherAbout = $about . '-a';
    $uuid = uniqid();
    $anotherUUID = uniqid();
    $xml0 = '<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:skos="http://www.w3.org/2004/02/skos/core#" xmlns:openskos="http://openskos.org/xmlns#" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmi="http://dublincore.org/documents/dcmi-terms/#" > ' .
      '<rdf:Description rdf:about="' . $about . '">' .
      '<rdf:type rdf:resource="http://www.w3.org/2004/02/skos/core#Concept"/>' .
      '<skos:prefLabel xml:lang="nl">' . $prefLabel . '</skos:prefLabel>' .
      '<skos:notation>' . $notation . '</skos:notation>' .
      '<skos:inScheme  rdf:resource="' . SCHEMA_URI_1 . '"/>' .
      '<openskos:uuid>' . $uuid . '</openskos:uuid>' .
      '</rdf:Description>' .
      '</rdf:RDF>';

    $response0 = $this->create($xml0, false);
    if ($response0->getStatus() == 201) {
      array_push($this->createdconcepts, $about);
      $xml1 = str_replace('testPrefLable_', '_another_testPrefLable_', $xml0);
      $xml1 = str_replace($about, $anotherAbout, $xml1);
      $xml1 = str_replace('<openskos:uuid>' . $uuid . '</openskos:uuid>', '<openskos:uuid>' . $anotherUUID . '</openskos:uuid>', $xml1);
      $response1 = $this->create($xml1, false);
      if ($response1->getStatus() == 201) {
        array_push($this->createdconcepts, $about);
      }
      $this->AssertEquals(400, $response1->getStatus());
    } else {
      throw new Exception('Creating first test concept has failed with the status: ' . $response0->getStatus());
    }
  }

  public function test06CreateConceptWithURIUniquePrefLabel() {
// Create concept without URI about, the xml is wrong
    print "\n\n test06 ... \n";
    $randomn = rand(0, 4096);
    $prefLabel = 'testPrefLable_' . $randomn;
    $wrongXml = '<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:skos="http://www.w3.org/2004/02/skos/core#" xmlns:openskos="http://openskos.org/xmlns#" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmi="http://dublincore.org/documents/dcmi-terms/#" > ' .
      '<rdf:Description>' .
      '<skos:prefLabel xml:lang="nl">' . $prefLabel . '</skos:prefLabel>' .
      '<skos:inScheme  rdf:resource="' . SCHEMA_URI_1 . '"/>' .
      '</rdf:Description' .
      '</rdf:RDF>';

    $response = $this->create($wrongXml);
    if ($response->getStatus() == 201) {
      array_push($this->createdconcepts, Utils::getAbout($response));
    }
    $this->AssertEquals(412, $response->getStatus());
  }

  public function test07CreateConceptWithoutUri() {
// Create a concept without Uri and with unique PrefLabel.
    print "\n\n test07 ... \n";
    $randomn = rand(0, 4092);
    $prefLabel = 'testPrefLable_' . $randomn;
    $xml = '<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:skos="http://www.w3.org/2004/02/skos/core#" xmlns:openskos="http://openskos.org/xmlns#" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmi="http://dublincore.org/documents/dcmi-terms/#" > ' .
      '<rdf:Description>' .
      '<rdf:type rdf:resource="http://www.w3.org/2004/02/skos/core#Concept"/>' .
      '<skos:prefLabel xml:lang="nl">' . $prefLabel . '</skos:prefLabel>' .
      '<skos:inScheme  rdf:resource="' . SCHEMA_URI_1 . '"/>' .
      '</rdf:Description>' .
      '</rdf:RDF>';

    $response = $this->create($xml);
    $this->AssertEquals(201, $response->getStatus());
    $this->CheckCreatedConcept($response);
  }

  public function test08CreateConceptWithoutUriAutogenerateFalse() {
    // Create a concept without Uri and with unique PrefLabel.  Autogenerate parameter is false
    print "\n\n test08 ... \n";
    $randomn = rand(0, 4092);
    $prefLabel = 'testPrefLable_' . $randomn;
    $xml = '<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:skos="http://www.w3.org/2004/02/skos/core#" xmlns:openskos="http://openskos.org/xmlns#" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmi="http://dublincore.org/documents/dcmi-terms/#" > ' .
      '<rdf:Description>' .
      '<rdf:type rdf:resource="http://www.w3.org/2004/02/skos/core#Concept"/>' .
      '<skos:prefLabel xml:lang="nl">' . $prefLabel . '</skos:prefLabel>' .
      '<skos:inScheme  rdf:resource="' . SCHEMA_URI_1 . '"/>' .
      '</rdf:Description>' .
      '</rdf:RDF>';

    $response = $this->create($xml, false);
    if ($response->getStatus() == 201) {
      array_push($this->createdconcepts, Utils::getAbout($response));
    }
    $this->AssertEquals(400, $response->getStatus());
  }

  public function test09CreateConceptWithoutUriPrefLabelExists() {
    // Create a concept without Uri and prefLabel is not unique within a scheme.
    print "\n\n test09 ... \n";
    $randomn = rand(0, 4092);
    $prefLabel = 'testPrefLable_' . $randomn;
    $altLabel = 'testAltPrefLable_' . $randomn;
    // create the first instance of the concept
    $xml0 = '<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:skos="http://www.w3.org/2004/02/skos/core#" xmlns:openskos="http://openskos.org/xmlns#" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmi="http://dublincore.org/documents/dcmi-terms/#" > ' .
      '<rdf:Description>' .
      '<rdf:type rdf:resource="http://www.w3.org/2004/02/skos/core#Concept"/>' .
      '<skos:prefLabel xml:lang="nl">' . $prefLabel . '</skos:prefLabel>' .
      '<skos:altLabel xml:lang="nl">' . $altLabel . '</skos:altLabel>' .
      '<skos:inScheme  rdf:resource="' . SCHEMA_URI_1 . '"/>' .
      '</rdf:Description>' .
      '</rdf:RDF>';

    $response0 = $this->create($xml0);

    if ($response0->getStatus() == 201) {
      // we can proceed with the test
      array_push($this->createdconcepts, Utils::getAbout($response0));
      $xml = str_replace('testAltLable_', '_another_testAltLable_', $xml0);
      $response = $this->create($xml);
      if ($response->getStatus() == 201) {
        array_push($this->createdconcepts, Utils::getAbout($response));
      }
      $this->AssertEquals(400, $response->getStatus());
    } else {
      throw new Exception('create the first test concept');
    }
  }

  public function test10CreateConceptWithoutUriButWithNotationUniquePrefLabel() {
    // Create a concept without Uri (no rdf:about), but with notation. prefLabel is unique.
    print "\n\n test10 ... \n";
    $randomn = rand(0, 4092);
    $prefLabel = 'testPrefLable_' . $randomn;
    $notation = 'testNotation_' . $randomn;
    $xml = '<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:skos="http://www.w3.org/2004/02/skos/core#" xmlns:openskos="http://openskos.org/xmlns#" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmi="http://dublincore.org/documents/dcmi-terms/#" > ' .
      '<rdf:Description>' .
      '<rdf:type rdf:resource="http://www.w3.org/2004/02/skos/core#Concept"/>' .
      '<skos:prefLabel xml:lang="nl">' . $prefLabel . '</skos:prefLabel>' .
      '<skos:inScheme  rdf:resource="http://meertens/scheme/example1"/>' .
      '<skos:inScheme  rdf:resource="' . SCHEMA_URI_1 . '"/>' .
      '<skos:notation>'.$notation.'</skos:notation>' .
      '</rdf:Description>' .
      '</rdf:RDF>';

    $response = $this->create($xml, false);
    if ($response->getStatus() === 201) {
      array_push($this->createdconcepts, Utils::getAbout($response));
    }
    $this->AssertEquals(400, $response->getStatus());
  }

  public function test10BCreateConceptWithoutUriButWithoutNotationUniquePrefLabel() {
    // Create a concept without Uri (no rdf:about), and no notation. prefLabel is unique.
    print "\n\n test10 ... \n";
    $randomn = rand(0, 4092);
    $uuid = uniqid();
    $prefLabel = 'testPrefLable_' . $randomn;
    $xml = '<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:skos="http://www.w3.org/2004/02/skos/core#" xmlns:openskos="http://openskos.org/xmlns#" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmi="http://dublincore.org/documents/dcmi-terms/#" > ' .
      '<rdf:Description>' .
      '<rdf:type rdf:resource="http://www.w3.org/2004/02/skos/core#Concept"/>' .
      '<skos:prefLabel xml:lang="nl">' . $prefLabel . '</skos:prefLabel>' .
      '<skos:inScheme  rdf:resource="' . SCHEMA_URI_1 . '"/>' .
      '<openskos:uuid>' . $uuid . '</openskos:uuid>' .
      '</rdf:Description>' .
      '</rdf:RDF>';

    $response = $this->create($xml, false);
    if ($response->getStatus() == 201) {
      array_push($this->createdconcepts, Utils::getAbout($response));
    }
    $this->AssertEquals(400, $response->getStatus());
  }

  private function CheckCreatedConcept($response) {
    $dom = new \Zend_Dom_Query();
    $dom->setDocumentXML($response->getBody());

    $elem = $dom->queryXpath('/rdf:RDF');
    $this->assertEquals($elem->current()->nodeType, XML_ELEMENT_NODE, 'The root node of the response is not an element');

    $description = $dom->queryXpath('/rdf:RDF/rdf:Description');
    $this->assertEquals(1, $description->count(), "rdf:Description element is not declared");

    $resURI = $description->current()->getAttribute("rdf:about");
    $this->assertNotEquals(null, $resURI, "No valid uri for SKOS concept (null value)");
    $this->assertNotEquals("", $resURI, "No valid uri for SKOS concept (empty-string value)");
    array_push($this->createdconcepts, $resURI);

    $status = $dom->queryXpath('/rdf:RDF/rdf:Description/openskos:status');
    $this->assertEquals(1, $status->count(), "No openkos:status element. ");
    $this->assertEquals("candidate", $status->current()->nodeValue, "Satus is not Candidate, as it must be by just created concept.");
  }

  private function deleteConcepts($uris) {
    foreach ($uris as $uri) {
      if ($uri != null) {
        $response = $this->deleteConcept($uri);
        if ($response->getStatus() !== 202 && $response->getStatus() !== 200) {
          throw Exception('delete while cleaning up database failed');
        }
      }
    }
  }

  private function deleteConcept($id) {
    $this->client->resetParameters();
    $this->client->setUri(API_BASE_URI . '/concept');
    $response = $this->client
      ->setParameterGet('tenant', TENANT)
      ->setParameterGet('key', API_KEY)
      ->setParameterGet('id', $id)
      ->request('DELETE');
    return $response;
  }

  private function create($xml, $autoGenerateIdentifiers = true) {
    $this->client->resetParameters();
    $this->client->setUri(API_BASE_URI . "/concept?");

    $response = $this->client
      ->setEncType('text/xml')
      ->setRawData($xml)
      ->setParameterGet('tenant', TENANT)
      ->setParameterGet('key', API_KEY)
      ->setParameterGet('autoGenerateIdentifiers', $autoGenerateIdentifiers)
      ->request('POST');

    return $response;
  }

}
