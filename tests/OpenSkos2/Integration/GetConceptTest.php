<?php

namespace Tests\OpenSkos2\Integration;

require_once 'AbstractTest.php';

class GetConceptTest extends AbstractTest
{

    private static $prefLabel;
    private static $altLabel;
    private static $hiddenLabel;
    private static $notation;
    private static $uuid;
    private static $about;
    private static $xml;

    public static function setUpBeforeClass()
    {
        self::$init = parse_ini_file(__DIR__ . '/../../../application/configs/application.ini');
        
        self::$client = new \Zend_Http_Client();
        self::$client->SetHeaders(array(
            'Accept' => 'text/html,application/xhtml+xml,application/xml',
            'Content-Type' => 'text/xml',
            'Accept-Language' => 'nl,en-US,en',
            'Accept-Encoding' => 'gzip, deflate',
            'Connection' => 'keep-alive')
        );
        $result = self::createTestConcept(API_KEY_EDITOR);
        if ($result['response']->getStatus() === 201) {
            self::$prefLabel = $result['prefLabel'];
            self::$altLabel = $result['altLabel'];
            self::$hiddenLabel = $result['hiddenLabel'];
            self::$notation = $result['notation'];
            self::$uuid = $result['uuid'];
            self::$about = $result['about'];
            self::$xml = $result['xml'];
        } else {
            throw new \Exception('Cannot create a test concept: ' . $result['response']->getStatus() . '; ' . $result['response']->getMessage());
        }
    }

    public static function tearDownAfterClass()
    {
        shell_exec("php " . SOURCE_DIR . "/tools/concept.php --key=" . API_KEY_ADMIN . " --tenant=" . TENANT_CODE . "  delete");
    }

    public function testViaPrefLabel2()
    {
        print "\n" . "Test: get concept-rdf via its prefLabel. ";
        self::$client->resetParameters();
        self::$client->setUri(API_BASE_URI . '/find-concepts?q=prefLabel:' . self::$prefLabel);
        $response = self::$client->request(\Zend_Http_Client::GET);
        $this->AssertEquals(200, $response->getStatus(), $response->getHeader("X-Error-Msg"));
        $this->assertionsForXMLRDFConcept($response, self::$prefLabel, self::$altLabel, self::$hiddenLabel, "nl", "integration test get concept", self::$notation, 1, 1);
    }

    
    public function testViaPrefLabel3()
    {
        print "\n" . "Test: get concept-rdf via its prefLabel where 'prefLabel' is a value of the request parameter 'label' ";
        self::$client->resetParameters();
        self::$client->setUri(API_BASE_URI . '/find-concepts?q=' . self::$prefLabel . '&label=prefLabel');
        $response = self::$client->request(\Zend_Http_Client::GET);
        $this->AssertEquals(200, $response->getStatus(), $response->getHeader("X-Error-Msg"));
        $this->assertionsForXMLRDFConcept($response, self::$prefLabel, self::$altLabel, self::$hiddenLabel, "nl", "integration test get concept", self::$notation, 1, 1);
    }

    public function testViaPrefLabelImplicit2()
    {
        print "\n" . "Test: get concept-rdf via its prefLabel, without saying that this is a pref label";
        self::$client->resetParameters();
        self::$client->setUri(API_BASE_URI . '/find-concepts?q=' . self::$prefLabel);
        $response = self::$client->request(\Zend_Http_Client::GET);
        $this->AssertEquals(200, $response->getStatus(), $response->getHeader("X-Error-Msg"));
    }

    public function testViaAltLabel()
    {
        print "\n" . "Test: get concept-rdf via its altLabel where 'altLabel' is a value of the request parameter 'label'";
        self::$client->resetParameters();
        self::$client->setUri(API_BASE_URI . '/find-concepts?q=' . self::$altLabel . '&label=altLabel');
        $response = self::$client->request(\Zend_Http_Client::GET);
        $this->AssertEquals(200, $response->getStatus(), $response->getHeader("X-Error-Msg"));
        $this->assertionsForXMLRDFConcept($response, self::$prefLabel, self::$altLabel, self::$hiddenLabel, "nl", "integration test get concept", self::$notation, 1, 1);
    }

    public function testViaAltLabelImplicit2()
    {
        print "\n" . "Test: get concept-rdf via its altLabel";
        self::$client->resetParameters();
        self::$client->setUri(API_BASE_URI . '/find-concepts?q=' . self::$altLabel);
        $response = self::$client->request(\Zend_Http_Client::GET);
        $this->AssertEquals(200, $response->getStatus(), $response->getHeader("X-Error-Msg"));
        $this->assertionsForXMLRDFConcept($response, self::$prefLabel, self::$altLabel, self::$hiddenLabel, "nl", "integration test get concept", self::$notation, 1, 1);
    }

    public function testViaHiddenLabelImplicit2()
    {
        print "\n" . "Test: get concept-rdf via its hiddenLabel";
        self::$client->resetParameters();
        self::$client->setUri(API_BASE_URI . '/find-concepts?q=' . self::$hiddenLabel);
        $response = self::$client->request(\Zend_Http_Client::GET);
        $this->AssertEquals(200, $response->getStatus(), $response->getHeader("X-Error-Msg"));
        $this->assertionsForXMLRDFConcept($response, self::$prefLabel, self::$altLabel, self::$hiddenLabel, "nl", "integration test get concept", self::$notation, 1, 1);
    }

    public function testViaPrefLabelIncomplete()
    {
        print "\n" . "Test: get concept-rdf via its prefLabel's prefix ";
        self::$client->resetParameters();
        self::$client->setUri(API_BASE_URI . '/find-concepts?q=prefLabel:testPrefLable*');
        $response = self::$client->request(\Zend_Http_Client::GET);
        $this->AssertEquals(200, $response->getStatus(), $response->getHeader("X-Error-Msg"));
        $this->assertionsForManyConcepts($response);
    }

    public function testViaPrefLabelIncompleteAndOneRow()
    {
        print "\n" . "Test: get concept-rdf via its prefLabel's prefix, but asking for 1 row ";
        self::$client->resetParameters();
        self::$client->setUri(API_BASE_URI . '/find-concepts?q=prefLabel:testPrefLable*&rows=1');
        $response = self::$client->request(\Zend_Http_Client::GET);
        $this->AssertEquals(200, $response->getStatus(), $response->getHeader("X-Error-Msg"));
        $this->assertionsForManyConceptsRows($response, 1);
    }

    public function testViaPrefLabelIncompleteAndTwoRows()
    {
        print "\n" . "Test: get concept-rdf via its prefLabel's prefix, but asking for 2 rows ";
        // create another concept
        $randomn = time();
        $prefLabel = 'testPrefLable_' . $randomn;
        $altLabel = 'testAltLable_' . $randomn;
        $hiddenLabel = 'testHiddenLable_' . $randomn;
        $notation = 'test-xxx-' . $randomn;
        $uuid_2 = self::$uuid . '_xyz';
        $about = API_BASE_URI . "/" . SET_CODE . "/" . $notation;
        $xml = '<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:skos="http://www.w3.org/2004/02/skos/core#" xmlns:openskos="http://openskos.org/xmlns#" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmi="http://dublincore.org/documents/dcmi-terms/#">' .
            '<rdf:Description rdf:about="' . $about . '">' .
            '<rdf:type rdf:resource="http://www.w3.org/2004/02/skos/core#Concept"/>' .
            '<skos:prefLabel xml:lang="nl">' . $prefLabel . '</skos:prefLabel>' .
            '<skos:altLabel xml:lang="nl">' . $altLabel . '</skos:altLabel>' .
            '<skos:hiddenLabel xml:lang="nl">' . $hiddenLabel . '</skos:hiddenLabel>' .
            '<openskos:uuid>' . $uuid_2 . '</openskos:uuid>' .
            '<skos:notation>' . $notation . '</skos:notation>' .
            '<skos:topConceptOf rdf:resource="' . SCHEMA1_URI . '"/>' .
            '<skos:inScheme  rdf:resource="' . SCHEMA1_URI . '"/>' .
            '<skos:definition xml:lang="nl">integration test get concept</skos:definition>' .
            '</rdf:Description>' .
            '</rdf:RDF>';


        $response1 = self::create($xml, API_KEY_EDITOR, 'concept');
        $this->AssertEquals(201, $response1->getStatus(), "\n Cannot perform the test because something is wrong with creating the second test concept: " . $response1->getHeader('X-Error-Msg'));
        self::$client->resetParameters();
        self::$client->setUri(API_BASE_URI . '/find-concepts?q=prefLabel:testPrefLable*&rows=2');
        $response = self::$client->request(\Zend_Http_Client::GET);
        $this->AssertEquals(200, $response->getStatus(), $response->getHeader("X-Error-Msg"));
        $this->assertionsForManyConceptsRows($response, 2);
    }

    public function testViaPrefLabelAndLangExist2()
    {
        print "\n" . "Test: get concept-rdf via its prefLabel and language. ";
        self::$client->resetParameters();
        self::$client->setUri(API_BASE_URI . '/find-concepts?q=prefLabel@nl:' . self::$prefLabel);
        $response = self::$client->request(\Zend_Http_Client::GET);
        $this->AssertEquals(200, $response->getStatus(), $response->getHeader("X-Error-Msg"));
        $this->assertionsForXMLRDFConcept($response, self::$prefLabel, self::$altLabel, self::$hiddenLabel, "nl", "integration test get concept", self::$notation, 1, 1);
    }

    public function testViaPrefLabelAndLangDoesNotExist2()
    {
        print "\n" . "Test: get concept-rdf via its prefLabel and laguage. Empty result. ";
        self::$client->resetParameters();
        self::$client->setUri(API_BASE_URI . '/find-concepts?q=prefLabel@en:' . self::$prefLabel);
        $response = self::$client->request(\Zend_Http_Client::GET);
        $this->AssertEquals(200, $response->getStatus(), $response->getHeader("X-Error-Msg"));
        $this->assertionsForManyConceptsZeroResults($response);
    }

    public function testViaPrefLabelPrefixAndLangExist2()
    {
        print "\n" . "Test: get concept-rdf via its prefLabel and language. ";
        self::$client->resetParameters();
        self::$client->setUri(API_BASE_URI . '/find-concepts?q=prefLabel@nl:testPref*');
        $response = self::$client->request(\Zend_Http_Client::GET);
        if ($response->getStatus() != 200) {
            print "\n " . $response->getMessage();
        }
        $this->AssertEquals(200, $response->getStatus(), $response->getHeader("X-Error-Msg"));
        $this->assertionsForManyConcepts($response);
    }

    public function testViaHandleXML()
    {
        print "\n" . "Test: get concept-rdf via its id. ";
        self::$client->resetParameters();
        self::$client->setUri(API_BASE_URI . '/concept?id=' . self::$about);
        $response = self::$client->request(\Zend_Http_Client::GET);
        $this->AssertEquals(200, $response->getStatus(), $response->getHeader("X-Error-Msg"));
        $this->assertionsForXMLRDFConcept($response, self::$prefLabel, self::$altLabel, self::$hiddenLabel, "nl", "integration test get concept", self::$notation, 1, 1);
    }

    public function testViaIdXML()
    {
        print "\n" . "Test: get concept-rdf via its id. ";
        self::$client->resetParameters();
        self::$client->setUri(API_BASE_URI . '/concept/' . self::$uuid);
        $response = self::$client->request(\Zend_Http_Client::GET);
        if ($response->getStatus() !== 200) {
            var_dump(API_BASE_URI . '/concept/' . self::$uuid);
            var_dump($response->getHeader("X-Error-Msg"));
        }
        $this->AssertEquals(200, $response->getStatus(), $response->getMessage());
        $this->assertionsForXMLRDFConcept($response, self::$prefLabel, self::$altLabel, self::$hiddenLabel, "nl", "integration test get concept", self::$notation, 1, 1);
    }

    public function testViaIdXMLrdf()
    {
        print "\n" . "Test: get concept-rdf via its id, context rdf explicit. ";
        self::$client->resetParameters();
        self::$client->setUri(API_BASE_URI . '/concept/' . self::$uuid . '.rdf');
        $response = self::$client->request(\Zend_Http_Client::GET);
        $this->AssertEquals(200, $response->getStatus(), $response->getHeader("X-Error-Msg"));
        $this->assertionsForXMLRDFConcept($response, self::$prefLabel, self::$altLabel, self::$hiddenLabel, "nl", "integration test get concept", self::$notation, 1, 1);
    }

    public function testViaIdHtml()
    {
        print "\n" . "Test: get concept-html via its id. ";
        self::$client->resetParameters();
        self::$client->setUri(API_BASE_URI . '/concept/' . self::$uuid . '.html');
        $response = self::$client->request(\Zend_Http_Client::GET);
        $this->AssertEquals(200, $response->getStatus(), $response->getHeader("X-Error-Msg"));
        $this->assertionsForHtmlConcept($response, self::$prefLabel, self::$altLabel, self::$hiddenLabel, "nl", "integration test get concept", self::$notation, 1, 1);
    }

    public function testViaHandleHtml()
    {
        print "\n" . "Test: get concept-html via its id. ";
        self::$client->setUri(API_BASE_URI . '/find-concepts?id=' . self::$about . '&format=html');
        $response = self::$client->request(\Zend_Http_Client::GET);
        $this->AssertEquals(200, $response->getStatus(), $response->getHeader("X-Error-Msg"));
        $this->assertionsForHtmlConcept($response, self::$prefLabel, self::$altLabel, self::$hiddenLabel, "nl", "integration test get concept", self::$notation, 1, 1);
    }

    public function testViaHandleJsonFiltered()
    {
        print "\n" . "Test: get concept-json with filtered fields via it handle ";
        self::$client->setUri(API_BASE_URI . '/find-concepts?id=' . self::$about . '&format=json&fl=uuid,uri,prefLabel');
        $response = self::$client->request(\Zend_Http_Client::GET);
        $this->AssertEquals(200, $response->getStatus(), $response->getHeader("X-Error-Msg"));
        $this->assertionsForJsonConceptFiltered($response, self::$uuid, self::$prefLabel);
    }

    public function testViaIdJson()
    {
        print "\n" . "Test: get concept-json via its id. ";
        self::$client->setUri(API_BASE_URI . '/concept/' . self::$uuid . '.json');
        $response = self::$client->request(\Zend_Http_Client::GET);
        $this->AssertEquals(200, $response->getStatus(), $response->getHeader("X-Error-Msg"));
        $this->assertionsForJsonConcept($response, self::$uuid, self::$prefLabel, self::$altLabel, self::$hiddenLabel, "nl", "integration test get concept", self::$notation, SCHEMA1_URI, SCHEMA1_URI);
    }

    public function testViaIdJsonP()
    {
        print "\n" . "Test: get concept-json via its id. ";
        self::$client->setUri(API_BASE_URI . '/concept/' . self::$uuid . '.jsonp?callback=test');
        $response = self::$client->request(\Zend_Http_Client::GET);
        $this->AssertEquals(200, $response->getStatus(), $response->getHeader("X-Error-Msg"));
        $this->assertionsForJsonPConcept($response, self::$uuid, self::$prefLabel, self::$altLabel, self::$hiddenLabel, "nl", "integration test get concept", self::$notation, SCHEMA1_URI, SCHEMA1_URI);
    }

    private function assertionsForManyConceptsRows($response, $rows)
    {

        $dom = new \Zend_Dom_Query();
        $xml = $response->getBody();
        $dom->setDocumentXML($xml);

        $sanityCheck = $dom->queryXpath('/rdf:RDF');
        $this->AssertEquals(1, count($sanityCheck));
        $results2 = $dom->query('rdf:Description');
        $this->AssertEquals($rows, count($results2), count($results2) . " rdf:Description is/are found");
    }

    private function assertionsForManyConceptsZeroResults($response)
    {

        $dom = new \Zend_Dom_Query();
        $xml = $response->getBody();
        $dom->setDocumentXML($xml);

        $sanityCheck = $dom->queryXpath('/rdf:RDF');
        $this->AssertEquals(1, count($sanityCheck));
        $results1 = $dom->queryXpath('/rdf:RDF')->current()->getAttribute('openskos:numFound');
        $results2 = $dom->queryXpath('/rdf:RDF/rdf:Description');
        $this->AssertEquals(0, count($results2));
        $this->AssertEquals(0, intval($results1));
    }

    private function assertionsForManyConcepts($response)
    {

        $dom = new \Zend_Dom_Query();
        $xml = $response->getBody();
        $dom->setDocumentXML($xml);

        $sanityCheck = $dom->queryXpath('/rdf:RDF');
        $this->AssertEquals(1, count($sanityCheck));
        $results1 = $dom->queryXpath('/rdf:RDF')->current()->getAttribute('openskos:numFound');
        $results2 = $dom->queryXpath('/rdf:RDF/rdf:Description');
        print "\n numFound =" . intval($results1) . "\n";
        $this->AssertEquals(intval($results1), count($results2));
    }

    private function assertionsForXMLRDFConcept($response, $prefLabel, $altLabel, $hiddenLabel, $lang, $definition, $notation, $topConceptOf, $inScheme)
    {

        $dom = new \Zend_Dom_Query();
        $xml = $response->getBody();
        $dom->setDocumentXML($xml);

        $results1 = $dom->queryXpath('/rdf:RDF/rdf:Description');
        $this->AssertEquals(1, count($results1), self::$prefLabel . "\n" . self::$xml . "\n");
        $this->AssertStringStartsWith(API_BASE_URI . "/" . SET_CODE, $results1->current()->getAttribute('rdf:about'));

        $results2 = $dom->query('rdf:type');
        $this->AssertEquals("http://www.w3.org/2004/02/skos/core#Concept", $results2->current()->getAttribute('rdf:resource'));

        $results3 = $dom->query('skos:notation');
        $this->AssertEquals($notation, $results3->current()->nodeValue);

        $results4 = $dom->query('skos:inScheme');
        $this->AssertEquals($inScheme, count($results4));

        $results5 = $dom->query('skos:topConceptOf');
        $this->AssertEquals($topConceptOf, count($results5));

        $results6 = $dom->query('skos:prefLabel');
        $this->AssertEquals($lang, $results6->current()->getAttribute('xml:lang'));
        $this->AssertEquals($prefLabel, $results6->current()->nodeValue);

        $results6a = $dom->query('skos:altLabel');
        $this->AssertEquals($lang, $results6a->current()->getAttribute('xml:lang'));
        $this->AssertEquals($altLabel, $results6a->current()->nodeValue);

        $results6b = $dom->query('skos:hiddenLabel');
        $this->AssertEquals($lang, $results6b->current()->getAttribute('xml:lang'));
        $this->AssertEquals($hiddenLabel, $results6b->current()->nodeValue);

        $results7 = $dom->query('skos:definition');
        $this->AssertEquals($definition, $results7->current()->nodeValue);

        $results9 = $dom->query('dcterms:creator');
        $this->AssertEquals(1, $results9->count());

        $results8 = $dom->query('openskos:set');
        $this->AssertEquals(SET_URI, $results8->current()->getAttribute('rdf:resource'));
    }

    private function assertionsForHTMLConcept($response, $prefLabel, $altLabel, $hiddenLabel, $lang, $definition, $notation, $topConceptOf, $inScheme)
    {
        $dom = new \Zend_Dom_Query();
        $dom->setDocumentHtml($response->getBody());

        //does not work because of . : $results1 = $dom->query('dl > dd  > a[href="http://hdl.handle.net/11148/CCR_C-4046_944cc750-1c29-ccf0-fb68-4d00385d7b42"]');
        $resultsUri1 = $dom->query('dl > dt');
        $propertyName1 = $this->getByIndex($resultsUri1, 2)->nodeValue;
        $this->AssertEquals("URI:", $propertyName1);
        $propertyName2 = $this->getByIndex($resultsUri1, 3)->nodeValue;
        $this->AssertEquals("SKOS Class:", $propertyName2);

        $resultsUri2 = $dom->query('dl > dd > a');
        $property = $this->getByIndex($resultsUri2, 2);
        $this->AssertEquals(self::$about, $property->nodeValue);
        $this->AssertEquals(self::$about, $property->getAttribute('href'));

        $h3s = $dom->query('h3');
        $inSchemeName = $this->getByIndex($h3s, 0)->nodeValue;
        $this->AssertEquals("inScheme", $inSchemeName);

        $lexLabels = $this->getByIndex($h3s, 1)->nodeValue;
        $this->AssertEquals("LexicalLabels", $lexLabels);


        $h4s = $dom->query('h4');
        $altLabelName = $this->getByIndex($h4s, 0)->nodeValue;
        $this->AssertEquals("skos:altLabel", trim($altLabelName));
        $prefLabelName = $this->getByIndex($h4s, 2)->nodeValue;
        $this->AssertEquals("skos:prefLabel", trim($prefLabelName));
        $notationName = $this->getByIndex($h4s, 3)->nodeValue;
        $this->AssertEquals("skos:notation", trim($notationName));

        $labels = $dom->query('ul > li > span');
        $prefLabelVal = $this->getByIndex($labels, 4)->nodeValue;
        $this->AssertEquals($prefLabel, $prefLabelVal);
    }

    private function assertionsForJsonConcept($response, $uuid, $prefLabel, $altLabel, $hiddenLabel, $lang, $definition, $notation, $topConceptOf, $inScheme)
    {
        $json = $response->getBody();
        $array = json_decode($json, true);
        $this->assertEquals($uuid, $array["uuid"]);
        $this->assertEquals($altLabel, $array["altLabel@nl"][0]);
        $this->assertEquals($prefLabel, $array["prefLabel@nl"]);
        return $json;
    }

    private function assertionsForJsonConceptFiltered($response, $uuid, $prefLabel)
    {
        $json = $response->getBody();
        $array = json_decode($json, true);
        //var_dump($json);
        //var_dump($array);
        $this->assertEquals(3, count($array));
        $this->assertEquals($uuid, $array["uuid"]);
        $this->assertEquals($prefLabel, $array["prefLabel@nl"]);
        return $json;
    }

    private function assertionsForJsonPConcept($response, $uuid, $prefLabel, $altLabel, $hiddenLabel, $lang, $definition, $notation, $topConceptOf, $inScheme)
    {
        $jsonp = $response->getBody();
        $json = substr($jsonp, strlen("test("), strlen($jsonp) - strlen("test(") - strlen(");"));
        $array = json_decode($json, true);
        $this->assertEquals($uuid, $array["uuid"]);
        $this->assertEquals($altLabel, $array["altLabel@nl"][0]);
        $this->assertEquals($prefLabel, $array["prefLabel@nl"]);
    }

}
