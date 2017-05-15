<?php

namespace Tests\OpenSkos2\Integration;

abstract class AbstractTest extends \PHPUnit_Framework_TestCase
{

    protected static $client;
    protected static $createdresourses;
    private static $resourceManager;

    protected static function create($xml, $apikey, $resourcetype, $autoGenerateIdentifiers = false)
    {
        self::$client->resetParameters();
        self::$client->setUri(API_BASE_URI . "/$resourcetype?");
        self::$client
            ->setEncType('text/xml')
            ->setRawData($xml)
            ->setParameterGet('tenant', TENANT_CODE)
            ->setParameterGet('key', $apikey)
            ->setParameterGet('autoGenerateIdentifiers', $autoGenerateIdentifiers);
        if (BACKWARD_COMPATIBLE) {
            self::$client->setParameterGet('collection', SET_CODE);
        } else {
            self::$client->setParameterGet('set', SET_CODE);
        };
        $response = self::$client->request('POST');
        return $response;
    }

    protected static function update($xml, $apikey, $resourcetype)
    {
        self::$client->resetParameters();
        self::$client->setUri(API_BASE_URI . "/$resourcetype?");
        self::$client
            ->setEncType('text/xml')
            ->setRawData($xml)
            ->setParameterGet('tenant', TENANT_CODE)
            ->setParameterGet('key', $apikey);
        if (BACKWARD_COMPATIBLE) {
            self::$client->setParameterGet('collection', SET_CODE);
        } else {
            self::$client->setParameterGet('set', SET_CODE);
        };
        $response = self::$client->request('PUT');
        return $response;
    }

    protected static function deleteResources($uris, $apikey, $resourcetype)
    { 
        foreach ($uris as $uri) {
            $response = self::delete($uri, $apikey, $resourcetype);
            var_dump("\n Cleaning data base: ". $response->getMessage(). "\n");
        }
    }

    protected static function deleteResourcesViaResourceManager($uris, $rdftype)
    { 
        foreach ($uris as $uri) {
            self::$resourceManager->delete($uri, $rdftype);
        }
    }

    protected static function delete($id, $apikey, $resoursetype)
    {
        $id_name = "uri";
        if ($resoursetype === 'concept') {
            $id_name = "id";
        }
        self::$client->resetParameters();
        self::$client->setUri(API_BASE_URI . "/$resoursetype");
        self::$client
            ->setParameterGet($id_name, $id)
            ->setParameterGet('tenant', TENANT_CODE)
            ->setParameterGet('key', $apikey);
        if (BACKWARD_COMPATIBLE) {
            self::$client->setParameterGet('collection', SET_CODE);
        } else {
            self::$client->setParameterGet('set', SET_CODE);
        };
        
        $response = self::$client->request('DELETE');
        return $response;
    }

    protected static function createTestSet($apikey)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<rdf:RDF xmlns:rdf = "http://www.w3.org/1999/02/22-rdf-syntax-ns#"
         xmlns:openskos = "http://openskos.org/xmlns#"
xmlns:dcterms = "http://purl.org/dc/terms/"
xmlns:dcmitype = "http://purl.org/dc/dcmitype#">
    <rdf:Description rdf:about="' . SET_URI . '">
        <openskos:code>' . SET_CODE . '</openskos:code>
        <openskos:uuid>' . SET_UUID . '</openskos:uuid>
        <dcterms:title xml:lang="en">' . SET_TITLE . '</dcterms:title>
        <dcterms:license rdf:resource="http://creativecommons.org/licenses/by/4.0/"></dcterms:license>
        <dcterms:publisher rdf:resource="' . TENANT_URI . '"></dcterms:publisher>
        <openskos:OAI_baseURL rdf:resource="https://openskos.meertens.knaw.nl/api/ergens"/>
        <openskos:allow_oai>true</openskos:allow_oai>
        <openskos:conceptBaseUri>http://example.com/collection-example</openskos:conceptBaseUri>
        <openskos:webpage rdf:resource="http://set-ergens"/>
    </rdf:Description>
</rdf:RDF>';
        $response = self::create($xml, $apikey, 'set');
        return $response;
    }

    private static function createTestSchema($apikey, $schema_uri, $schema_uuid, $schema_title, $set_uri)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<rdf:RDF xmlns:rdf = "http://www.w3.org/1999/02/22-rdf-syntax-ns#"
         xmlns:openskos = "http://openskos.org/xmlns#"
xmlns:dcterms = "http://purl.org/dc/terms/">
    <rdf:Description rdf:about="' . $schema_uri . '">
        <rdf:type rdf:resource="http://www.w3.org/2004/02/skos/core#ConceptScheme"/> 
        <dcterms:title  xml:lang="en">' . $schema_title . '</dcterms:title>
        <openskos:set rdf:resource="' . $set_uri . '"/>
        <openskos:uuid>' . $schema_uuid . '</openskos:uuid>
    </rdf:Description>
</rdf:RDF>
';
        $response = self::create($xml, $apikey, 'conceptscheme');
        return $response;
    }

    protected static function createTestSchema1($apikey)
    {
        return self::createTestSchema($apikey, SCHEMA1_URI, SCHEMA1_UUID, SCHEMA1_TITLE, SET_URI);
    }

    protected static function createTestSchema2($apikey)
    {
        return self::createTestSchema($apikey, SCHEMA2_URI, SCHEMA2_UUID, SCHEMA2_TITLE, SET_URI);
    }

    protected function createTestConcept($apikey)
    {
        $randomn = time().uniqid();
        $prefLabel = 'testPrefLable_' . $randomn;
        $altLabel = 'testAltLable_' . $randomn;
        $hiddenLabel = 'testHiddenLable_' . $randomn;
        $notation = 'test-xxx-' . $randomn;
        $uuid = uniqid() . $randomn;
        $about = API_BASE_URI . "/" . SET_CODE . "/" . $notation;
        $xml = '<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:skos="http://www.w3.org/2004/02/skos/core#" xmlns:openskos="http://openskos.org/xmlns#" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmi="http://dublincore.org/documents/dcmi-terms/#">' .
            '<rdf:Description rdf:about="' . $about . '">' .
            '<rdf:type rdf:resource="http://www.w3.org/2004/02/skos/core#Concept"/>' .
            '<skos:prefLabel xml:lang="nl">' . $prefLabel . '</skos:prefLabel>' .
            '<skos:altLabel xml:lang="nl">' . $altLabel . '</skos:altLabel>' .
            '<skos:hiddenLabel xml:lang="nl">' . $hiddenLabel . '</skos:hiddenLabel>' .
            '<openskos:uuid>' . $uuid . '</openskos:uuid>' .
            '<skos:notation>' . $notation . '</skos:notation>' .
            '<skos:topConceptOf rdf:resource="' . SCHEMA1_URI . '"/>' .
            '<skos:inScheme  rdf:resource="' . SCHEMA1_URI . '"/>' .
            '<skos:definition xml:lang="nl">integration test get concept</skos:definition>' .
            '</rdf:Description>' .
            '</rdf:RDF>';

        $response = self::create($xml, $apikey, 'concept');
        $retVal = array('xml' => $xml, 'prefLabel' => $prefLabel, 'altLabel' => $altLabel, 'hiddenLabel' => $hiddenLabel, 'uuid' => $uuid, 'about' => $about, 'notation' => $notation, 'response' => $response);
        return $retVal;
    }

    protected function getAbout($response)
    {
        $dom = new \Zend_Dom_Query();
        $dom->setDocumentXML($response->getBody());
        $description = $dom->queryXpath('/rdf:RDF/rdf:Description');
        if ($description->count() < 1) {
            throw Exception("rdf:Description element is not declared");
        }
        $resURI = $description->current()->getAttribute("rdf:about");
        if ($resURI === "") {
            throw new \Exception("No valid uri for SKOS concept");
        }
        return $resURI;
    }

    protected function getByIndex($list, $index)
    {
        if ($index < 0 || $index >= count($list)) {
            return null;
        }
        $list->rewind();
        $i = 0;
        while ($i < $index) {
            $list->next();
            $i++;
        }
        return $list->current();
    }

    protected function jsonP_decode_parameters($input, $callbackName)
    {
        $inputTrimmed = trim($input);
        $errorMessage = "The input value \n" . $input . "\n is not a valid jsonp value. \n";
        $begin = strpos($inputTrimmed, $callbackName . '(');
        if ($begin != 0) {
            if (!$begin) {
                print $errorMessage;
                print "\n Reason: it does not contain <callbackname>( \n";
                return null;
            }
            print $errorMessage;
            print "\n Reason: it does not start with <callbackname>( \n";
            return null;
        }
        $end = strrpos($inputTrimmed, ");");
        if ($end != strlen($inputTrimmed) - 2) {
            if (!$end) {
                print $errorMessage;
                print "\n Reason: it does not contain ); \n";
                return null;
            }
            print $errorMessage;
            print "\n Reason: it does not end with ); \n";
            return null;
        }
        $length = strlen($inputTrimmed) - (strlen($callbackName) + 1) - 2; // the input string should start with <callbackname( and end with );
        $parameters = substr($inputTrimmed, strlen($callbackName) + 1, $length);
        return json_decode($parameters, true);
    }

    protected function allResources($resourcetype)
    {
        print "\n Test: get all $resourcetype in default format ... ";
        $response = $this->getResource(API_BASE_URI . "/$resourcetype", 'text/xml');
        $this->AssertEquals(200, $response->getStatus(), $response->getMessage());
        $this->assertionsXMLRDFResources($response);
    }

    protected function allResourcesJson($resourcetype)
    {
        print "\n Test: get all $resourcetype in json ... ";
        $response = $this->getResource(API_BASE_URI . "/$resourcetype?format=json", 'application/json');
        $this->AssertEquals(200, $response->getStatus(), $response->getMessage());
        $json = $response->getBody();
        $resources = json_decode($json, true);
        $this->assertionsJsonResources($resources["response"], false);
    }

    protected function allResourcesJsonP($resourcetype)
    {
        print "\n Test: get all $resourcetype in jsonp ... ";
        $response = $this->getResource(API_BASE_URI . "/$resourcetype?format=jsonp&callback=" . CALLBACK_NAME, 'application/json');
        $this->AssertEquals(200, $response->getStatus(), $response->getMessage());
        $jsonP = $response->getBody();
        $resources = $this->jsonP_decode_parameters($jsonP, CALLBACK_NAME);
        $this->assertionsJsonResources($resources["response"], false);
    }

    protected function allResourcesRDFXML($resourcetype)
    {
        print "\n Test: get all $resourcetype rdf/xml explicit... ";
        $response = $this->getResource(API_BASE_URI . "/$resourcetype?format=rdf", 'text/xml');
        $this->AssertEquals(200, $response->getStatus(), $response->getMessage());
        $this->assertionsXMLRDFResources($response);
    }

    protected function allResourcesHTML($resourcetype)
    {
        print "\n Test: get all $resourcetype html... ";
        $response = $this->getResource(API_BASE_URI . "/$resourcetype?format=html", 'text/html');
        $this->AssertEquals(200, $response->getStatus(), $response->getMessage());
        $this->assertionsHTMLAllResources($response);
    }

    protected function resource($resourcetype, $id)
    {
        print "\n Test: get a $resourcetype in default format ... ";
        $response = $this->getResource(API_BASE_URI . "/$resourcetype/$id", 'text/xml');
        $this->AssertEquals(200, $response->getStatus(), $response->getMessage());
        $dom = new \Zend_Dom_Query();
        $dom->setDocumentXML($response->getBody());
        $this->assertionsXMLRDFResource($dom, 0);
    }

    protected function resourceJson($resourcetype, $id)
    {
        print "\n Test: get a $resourcetype in json ... ";
        $response = $this->getResource(API_BASE_URI . "/$resourcetype/$id.json", 'application/json');
        $this->AssertEquals(200, $response->getStatus(), $response->getMessage());
        $json = $response->getBody();
        $resource = json_decode($json, true);
        $this->assertionsJsonResource($resource, true);
    }

    protected function resourceJsonP($resourcetype, $id)
    {
        print "\n Test: get a $resourcetype in jsonp ... ";
        $response = $this->getResource(API_BASE_URI . "/$resourcetype/$id.jsonp?callback=" . CALLBACK_NAME, 'application/json');
        $this->AssertEquals(200, $response->getStatus(), $response->getMessage());
        $json = $response->getBody();
        $resource = $this->jsonP_decode_parameters($json, CALLBACK_NAME);
        $this->assertionsJsonResource($resource, true);
    }

    protected function resourceHTML($resourcetype, $id)
    {
        print "\n Test: get a $resourcetype in html ... ";
        $response = $this->getResource(API_BASE_URI . "/$resourcetype/$id.html", 'text/html');
        $this->AssertEquals(200, $response->getStatus(), $response->getMessage());
        $dom = new \Zend_Dom_Query();
        $dom->setDocumentHTML($response->getBody());
        $this->assertionsHTMLResource($dom, 0);
    }

    ////////////////////////////////////
    protected function assertionsXMLRDFResources($response)
    {
        
    }

    protected function assertionsJsonResources($resource)
    {
        
    }

    protected function assertionsHTMLAllResources($response)
    {
        
    }

    protected function assertionsXMLRDFResource(\Zend_Dom_Query $dom)
    {
        
    }

    protected function assertionsJsonResource($resource, bool $isSingleResource)
    {
        
    }

    protected function assertionsHTMLResource(\Zend_Dom_Query $dom, $i)
    {
        
    }

    protected function getResource($requestString, $contentType)
    {
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
