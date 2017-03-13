<?php

namespace Tests\OpenSkos2\Integration;
require_once 'AbstractTest.php';


class AutocompleteTest extends AbstractTest {

  private static $prefix;
  private static $labelMap;

  // static:   is a demand of the PHPunit test library
  public static function setUpBeforeClass() {

    self::$client = new \Zend_Http_Client();
    self::$client->setConfig(array(
      'maxredirects' => 0,
      'timeout' => 30));
    self::$client->SetHeaders(array(
      'Accept' => 'text/html,application/xhtml+xml,application/xml',
      'Content-Type' => 'application/json',
      'Accept-Language' => 'nl,en-US,en',
      'Accept-Encoding' => 'gzip, deflate',
      'Connection' => 'keep-alive')
    );
    self::$createdconcepts = array();
    self::$labelMap = array(
      PREF_LABEL => PREF_LABEL . "_",
      ALT_LABEL => ALT_LABEL . "_",
      HID_LABEL => HID_LABEL . "_",
      NOTATION => NOTATION . "_",
    );

    // create test concepts

    $letters = range('a', 'z');
    self::$prefix[0] = uniqid();
    $i = 1;

    foreach ($letters as $letter) {
      self::$prefix[$i] = self::$prefix[$i - 1] . $letter;
      $randomn = rand(0, 10000);
      $prefLabel = self::$labelMap[PREF_LABEL] . self::$prefix[$i] . $randomn;
      $altLabel = self::$labelMap[ALT_LABEL] . self::$prefix[$i] . $randomn;
      $hiddenLabel = self::$labelMap[HID_LABEL] . self::$prefix[$i] . $randomn;
      $notation = self::$labelMap[NOTATION] . self::$prefix[$i] . $randomn;
      $uuid = uniqid();
      $about = API_BASE_URI . "/" . SET_CODE . "/" . $notation;
      $xml = '<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:skos="http://www.w3.org/2004/02/skos/core#" xmlns:openskos="http://openskos.org/xmlns#" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmi="http://dublincore.org/documents/dcmi-terms/#">' .
        '<rdf:Description rdf:about="' . $about . '">' .
        '<skos:prefLabel xml:lang="nl">' . $prefLabel . '</skos:prefLabel>' .
        '<skos:altLabel xml:lang="nl">' . $altLabel . '</skos:altLabel>' .
        '<skos:hiddenLabel xml:lang="nl">' . $hiddenLabel . '</skos:hiddenLabel>' .
        '<openskos:uuid>' . $uuid . '</openskos:uuid>' .
        '<skos:inScheme  rdf:resource="' . SCHEMA_URI_1 . '"/>' .
        '<openskos:status>approved</openskos:status>' .
        '<skos:notation>' . $notation . '</skos:notation>' .
        '<skos:definition xml:lang="nl">integration test autocomplete</skos:definition>' .
        '</rdf:Description>' .
        '</rdf:RDF>';

      
      $response0 = self::create($xml);
      if ($response0->getStatus() !== 201) {
        echo 'concept' . $i;
        throw new \Exception("creating a test concept has failed. Status ". $response0 ->getStatus() . ' Message: ' . $response0->getMessage());
      } else { // things went well, but when submitting a concept is status is automatically reset to "candidate";
        // now update to change the status for "approved", otherwise autocomplete would not react
        $response1 = self::update($xml);
        if ($response1->getStatus() !== 200) {
          throw new \Exception("setting status approved for a test concept has failed. ". " Status ". $response1 ->getStatus() . ' Message: ' . $response1->getMessage());
        }
      }
      array_push(self::$createdconcepts, $about);
      echo $i;
      $i++;
    }
  }

  
  // delete all created concepts
  public static function tearDownAfterClass() {
    self::deleteConcepts(self::$createdconcepts);
  }


  public function testAutocompleteInLoopNoParams() {
    print "\n testAutocomplete in loop ";
    $numPrefixes = count(self::$prefix);
    $lim = $numPrefixes - 1; // must be 26
    for ($i = 1; $i <= $lim; $i++) {
      $word = self::$labelMap[PREF_LABEL] . self::$prefix[$i];
      $response = $this->autocomplete($word, "");
      if ($response->getStatus() != 200) {
        throw new Excpetion('Failure: '. $response);
      }
      $this->AssertEquals(200, $response->getStatus());
      $json = $response->getBody();
      $arrayjson = json_decode($json, true);
      //var_dump($array);
      // todo: for now the spec is unclear. correct after the spec is clarified.
      $this->AssertEquals($numPrefixes - $i, count($arrayjson));
    }
  }

  
  public function testAutocompleteSearchAltLabel() {
    print "\n testAutocomplete search alt Label \n";
    $word = self::$labelMap[ALT_LABEL] . self::$prefix[1]; // prefLabel<someuuid>a.
    //print "\n $word \n";
    $response = $this->autocomplete($word, "?searchLabel=altLabel");
    if ($response->getStatus() != 200) {
      throw new Excpetion('Failure: '. $response, 'autocomplete on word ' . $word);
    }
    $this->AssertEquals(200, $response->getStatus());
    $json = $response->getBody();
    //var_dump($json);
    $arrayjson = json_decode($json, true);
    $this->AssertEquals(26, count($arrayjson));
  }

  public function testAutocompleteSearchAltLabelWithNoOccurences() {
    print "\n testAutocomplete search alt Label";
    $searchword = self::$labelMap[PREF_LABEL] . self::$prefix[1]; // should not occur in alt labels
    $response = $this->autocomplete($searchword, "?searchLabel=altLabel");
    if ($response->getStatus() != 200) {
      throw new Excpetion('Failure: '. $response, 'autocomplete on word ' . $searchword);
    }
    $this->AssertEquals(200, $response->getStatus());
    $json = $response->getBody();
    $arrayjson = json_decode($json, true);
    $this->AssertEquals(0, count($arrayjson));
  }

  public function testAutocompleteReturnAltLabel() {
    print "\n testAutocomplete return alt Label";
    $searchword = self::$labelMap[PREF_LABEL] . self::$prefix[1]; // prefLabel_<someuuid>a.
    $returnword = self::$labelMap[ALT_LABEL] . self::$prefix[1]; // altLabel_<someuuid>a.
    $response = $this->autocomplete($searchword, "?returnLabel=altLabel");
    if ($response->getStatus() != 200) {
      throw new Exception('Failure:'.$response, 'autocomplete on word ' . $searchword);
    }
    $this->AssertEquals(200, $response->getStatus());
    $json = $response->getBody();
    $arrayjson = json_decode($json, true);
    $this->AssertEquals(26, count($arrayjson));
    for ($i = 0; $i < count($arrayjson); $i++) {
      $this->assertStringStartsWith($returnword, $arrayjson[$i]);
    }
  }

  public function testAutocompleteLangNL() {
    print "\n testAutocomplete search pref Label";
    $word = self::$labelMap[PREF_LABEL] . self::$prefix[1]; // prefLabel<someuuid>a.
    $response = $this->autocomplete($word, "?lang=nl");
    if ($response->getStatus() != 200) {
      throw new Exception('Failure: '.$response, 'autocomplete on word ' . $word . "?lang=nl");
    }
    $this->AssertEquals(200, $response->getStatus());
    $json = $response->getBody();
    $arrayjson = json_decode($json, true);
    $this->AssertEquals(26, count($arrayjson));
  }

  // to do: make more advanced test with "en" non-zero occurences or so
  public function testAutocompleteLangEN() {
    print "\n testAutocomplete search pref Label";
    $word = self::$labelMap[PREF_LABEL] . self::$prefix[1]; // prefLabel<someuuid>a.
    $response = $this->autocomplete($word, "?lang=en");
    if ($response->getStatus() != 200) {
      throw new Exception('Failure: '.$response, 'autocomplete on word ' . $word . "?lang=en (does not exists)");
    }
    $this->AssertEquals(200, $response->getStatus());
    $json = $response->getBody();
    $arrayjson = json_decode($json, true);
    $this->AssertEquals(0, count($arrayjson));
  }

  public function testAutocompleteFormatHTML() {
    print "\n testAutocomplete search pref Label";
    $word = self::$labelMap[PREF_LABEL] . self::$prefix[1]; // prefLabel<someuuid>a.
    $response = $this->autocomplete($word, "?format=html");
    if ($response->getStatus() != 200) {
      throw new Exception('Failure:'.$response, 'autocomplete on word ' . $word . "?format=html");
    }
    $this->AssertEquals(200, $response->getStatus());
    // todo: add some chek when it becomes clear how the output looks like
  }
  
  private function autocomplete($word, $parameterString) {
        self::$client ->resetParameters();
        $uri = API_BASE_URI . '/autocomplete/' . $word .  $parameterString;
        self::$client ->setUri($uri);
        $response = self::$client -> request(\Zend_Http_Client::GET);
        return $response;
    }
  
  
}
