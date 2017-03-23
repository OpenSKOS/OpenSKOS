<?php

namespace Tests\OpenSkos2\Integration;

require_once 'AbstractTest.php';

abstract class AbstractTest extends \PHPUnit_Framework_TestCase {

  protected static $client;
  protected static $createdresources;

  protected static function create($xml, $apikey, $resourcetype, $autoGenerateIdentifiers = false) {
    self::$client->resetParameters();
    self::$client->setUri(API_BASE_URI . "/$resourcetype?");
    $response = self::$client
      ->setEncType('text/xml')
      ->setRawData($xml)
      ->setParameterGet('tenant', TENANT)
      ->setParameterGet('key', $apikey)
      ->setParameterGet('autoGenerateIdentifiers', $autoGenerateIdentifiers)
      ->request('POST');
    return $response;
  }

  protected static function update($xml, $apikey, $resourcetype) {
    self::$client->resetParameters();
    self::$client->setUri(API_BASE_URI . "/$resourcetype?");

    $response = self::$client
      ->setEncType('text/xml')
      ->setRawData($xml)
      ->setParameterGet('tenant', TENANT)
      ->setParameterGet('key', $apikey)
      ->request('PUT');

    return $response;
  }

  protected static function deleteConcepts($uris, $apikey, $resourcetype) {
    foreach ($uris as $uri) {
      if ($uri != null) {
        $response = self::delete($uri, $apikey, $resourcetype);
        if ($response->getStatus() !== 202) {
          throw new \Exception('delete ' . $uri . ' while cleaning up database failed: ' . $response->getStatus() . ", " . $response->getMessage());
        }
      }
    }
  }

  protected static function delete($id, $apikey, $resourcetype) {
    self::$client->resetParameters();
    self::$client->setUri(API_BASE_URI . "/$resourcetype");
    $response = self::$client
      ->setParameterGet('tenant', TENANT)
      ->setParameterGet('key', $apikey)
      ->setParameterGet('id', $id)
      ->request('DELETE');
    return $response;
  }

  protected function getAbout($response) {
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

  protected function getByIndex($list, $index) {
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

  protected function jsonP_decode_parameters($input, $callbackName) {
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

  protected function allResources($resourcetype) {
    print "\n Test: get all $resourcetype in default format ... ";
    $response = $this->getResource(API_BASE_URI . "/$resourcetype", 'text/xml');
    $this->AssertEquals(200, $response->getStatus(), $response->getMessage());
    $this->assertionsXMLRDFResources($response);
  }

  protected function allResourcesJson($resourcetype) {
    print "\n Test: get all $resourcetype in json ... ";
    $response = $this->getResource(API_BASE_URI . "/$resourcetype?format=json", 'application/json');
    $this->AssertEquals(200, $response->getStatus(), $response->getMessage());
    $json = $response->getBody();
    $resources = json_decode($json, true);
    $this->assertionsJsonResources($resources["response"]);
  }

  protected function allResourcesJsonP($resourcetype) {
    print "\n Test: get all $resourcetype in jsonp ... ";
    $response = $this->getResource(API_BASE_URI . "/$resourcetype?format=jsonp&callback=" . CALLBACK_NAME, 'application/json');
    $this->AssertEquals(200, $response->getStatus(), $response->getMessage());
    $jsonP = $response->getBody();
    $resources = $this->jsonP_decode_parameters($jsonP, CALLBACK_NAME);
    $this->assertionsJsonResources($resources["response"]);
  }

  protected function allResourcesRDFXML($resourcetype) {
    print "\n Test: get all $resourcetype rdf/xml explicit... ";
    $response = $this->getResource(API_BASE_URI . "/$resourcetype?format=rdf", 'text/xml');
    $this->AssertEquals(200, $response->getStatus(), $response->getMessage());
    $this->assertionsXMLRDFResources($response);
  }

  protected function allResourcesHTML($resourcetype) {
    print "\n Test: get all $resourcetype html... ";
    $response = $this->getResource(API_BASE_URI . "/$resourcetype?format=html", 'text/html');
    $this->AssertEquals(200, $response->getStatus(), $response->getMessage());
    $this->assertionsHTMLAllResources($response);
  }

  protected function resource($resourcetype, $id) {
    print "\n Test: get a $resourcetype in default format ... ";
    $response = $this->getResource(API_BASE_URI . "/$resourcetype/$id", 'text/xml');
    $this->AssertEquals(200, $response->getStatus(), $response->getMessage());
    $dom = new \Zend_Dom_Query();
    $dom->setDocumentXML($response->getBody());
    $this->assertionsXMLRDFResource($dom, 0);
  }

  protected function resourceJson($resourcetype, $id) {
    print "\n Test: get a $resourcetype in json ... ";
    $response = $this->getResource(API_BASE_URI . "/$resourcetype/$id.json", 'application/json');
    $this->AssertEquals(200, $response->getStatus(), $response->getMessage());
    $json = $response->getBody();
    $resource = json_decode($json, true);
    $this->assertionsJsonResource($resource, 0);
  }

  protected function resourceJsonP($resourcetype, $id) {
    print "\n Test: get a $resourcetype in jsonp ... ";
    $response = $this->getResource(API_BASE_URI . "/$resourcetype/$id.jsonp?callback=" . CALLBACK_NAME, 'application/json');
    $this->AssertEquals(200, $response->getStatus(), $response->getMessage());
    $json = $response->getBody();
    $resource = $this->jsonP_decode_parameters($json, CALLBACK_NAME);
    $this->assertionsJsonResource($resource);
  }

  protected function resourceHTML($resourcetype, $id) {
    print "\n Test: get a $resourcetype in html ... ";
    $response = $this->getResource(API_BASE_URI . "/$resourcetype/$id.html", 'text/html');
    $this->AssertEquals(200, $response->getStatus(), $response->getMessage());
    $dom = new \Zend_Dom_Query();
    $dom->setDocumentHTML($response->getBody());
    $this->assertionsHTMLResource($dom, 0);
  }

  ////////////////////////////////////
  protected function assertionsXMLRDFResources($response) {
  }

  protected function assertionsJsonResources($resource) {
  }

  protected function assertionsHTMLAllResources($response) {
  }

  protected function assertionsXMLRDFResource(\Zend_Dom_Query $dom) {
  }

  protected function assertionsJsonResource($resource) {
  }

 
  protected function assertionsHTMLResource(\Zend_Dom_Query $dom, $i) {
  }
  
  protected function getResource($requestString, $contentType) {
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
