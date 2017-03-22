<?php

namespace Tests\OpenSkos2\Integration;

require_once 'AbstractTest.php';

class DeleteConceptTest extends AbstractTest {

  private $prefLabel;
  private $altLabel;
  private $hiddenLabel;
  private $notation;
  private $uuid;
  private $xml;
  private $about;

  
  public static function tearDownAfterClass() {
  }

  public function testDeleteCandidateByAdmin() {
    print "\n deleting concept with candidate status by admin... \n";
    $this->createTestConcept(API_KEY_EDITOR);
    $response = $this->delete($this->about, API_KEY_ADMIN, 'concept');
    $this->AssertEquals(202, $response->getStatus());
    self::$client->setUri(API_BASE_URI . '/concept?id=' . $this->uuid);
    $checkResponse = self::$client->request('GET');
    $this->AssertEquals(410, $checkResponse->getStatus(), 'Admin was not able to delete an approved concept or something else went wrong. Getting that concept gives status ' . $checkResponse->getStatus());
  }
  
  public function testDeleteCandidatebyOwner() { // TODO ///
    print "\n deleting concept with candidate status by the owner-deitor... \n";
    $this->createTestConcept(API_KEY_EDITOR);
    $response = $this->delete($this->about, API_KEY_EDITOR, 'concept');
    $this->AssertEquals(202, $response->getStatus());
    self::$client->setUri(API_BASE_URI . '/concept?id=' . $this->uuid);
    $checkResponse = self::$client->request('GET');
    $this->AssertEquals(410, $checkResponse->getStatus(), 'Admin was not able to delete an approved concept or something else went wrong. Getting that concept gives status ' . $checkResponse->getStatus());
  }
  
  public function testDeleteCandidateByGuest() {
    print "\n deleting concept with candidate status by guest...\n";
    $this->createTestConcept(API_KEY_EDITOR);
    $response = $this->delete($this->about, API_KEY_GUEST, 'concept');
    $this->AssertEquals(403, $response->getStatus());
  }

  public function testDeleteApprovedByAdmin() {
    print "\n deleting concept with approved status by admin ...\n";
    $this->createTestConcept(API_KEY_EDITOR);
    self::update($this->xml, API_KEY_EDITOR, 'concept'); // updating will make the status "approved" 
    $response = $this->delete($this->about, API_KEY_ADMIN, 'concept');
    $this->AssertEquals(202, $response->getStatus());
    self::$client->setUri(API_BASE_URI . '/concept?id=' . $this->uuid);
    $checkResponse = self::$client->request('GET');
    $this->AssertEquals(410, $checkResponse->getStatus(), 'Admin was not able to delete an approved concept or something else went wrong. Getting that concept gives status ' . $checkResponse->getStatus());
  }
  
  public function testDeleteApprovedByOwner() { 
    print "\n deleting concept with approved status by an owner-editor ...";
    $this->createTestConcept(API_KEY_EDITOR);
    self::update($this->xml, API_KEY_EDITOR, 'concept'); // updating will make the status "approved" 
    $response = $this->delete($this->about, API_KEY_EDITOR, 'concept');
    $this->AssertEquals(202, $response->getStatus(), "the owner could not delete their own concept");
  }

  
  public function testDeleteApprovedByGuest() {
    print "\n deleting concept with approved status by a guest ...";
    $this->createTestConcept(API_KEY_EDITOR);
    self::update($this->xml, API_KEY_EDITOR, 'concept'); // updating will make the status "approved" 
    $response = $this->delete($this->about, API_KEY_GUEST, 'concept');
    $this->AssertEquals(403, $response->getStatus());
  }

  private function createTestConcept($apikey) {
    self::$createdresources = array();
    self::$client = new \Zend_Http_Client();
    self::$client->SetHeaders(array(
      'Accept' => 'text/html,application/xhtml+xml,application/xml',
      'Content-Type' => 'text/xml',
      'Accept-Language' => 'nl,en-US,en',
      'Accept-Encoding' => 'gzip, deflate',
      'Connection' => 'keep-alive')
    );
    // create a test concept
    $randomn = time();
    $this->prefLabel = 'testPrefLable_' . $randomn;
    $this->altLabel = 'testAltLable_' . $randomn;
    $this->hiddenLabel = 'testHiddenLable_' . $randomn;
    $this->notation = 'test-xxx-' . $randomn;
    $this->uuid = uniqid() . uniqid();
    $this->about = API_BASE_URI . "/" . SET_CODE . "/" . $this->notation;
    $this->xml = '<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:skos="http://www.w3.org/2004/02/skos/core#" xmlns:openskos="http://openskos.org/xmlns#" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmi="http://dublincore.org/documents/dcmi-terms/#">' .
      '<rdf:Description rdf:about="' . $this->about . '">' .
      '<skos:prefLabel xml:lang="nl">' . $this->prefLabel . '</skos:prefLabel>' .
      '<skos:altLabel xml:lang="nl">' . $this->altLabel . '</skos:altLabel>' .
      '<skos:hiddenLabel xml:lang="nl">' . $this->hiddenLabel . '</skos:hiddenLabel>' .
      '<openskos:uuid>' . $this->uuid . '</openskos:uuid>' .
      '<skos:notation>' . $this->notation . '</skos:notation>' .
      '<skos:topConceptOf rdf:resource="' . SCHEMA_URI_1 . '"/>' .
      '<skos:inScheme  rdf:resource="' . SCHEMA_URI_1 . '"/>' .
      '<openskos:status>approved</openskos:status>' .
      '<skos:definition xml:lang="nl">integration test get concept</skos:definition>' .
      '</rdf:Description>' .
      '</rdf:RDF>';

    $response = self::create($this->xml, $apikey, 'concept'); // the first attempt to create a concept will geive a concepts with the candidate status
    if ($response->getStatus() === 201) {
      array_push(self::$createdresources, self::getAbout($response));
    } else {
      throw new \Exception('Cannot create test concept: ' . $response->getStatus() . " with the message " . $response->getMessage());
    }
  }
}
