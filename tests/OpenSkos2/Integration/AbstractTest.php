<?php

namespace Tests\OpenSkos2\Integration;

abstract class AbstractTest extends \PHPUnit_Framework_TestCase {

  protected $client;
  protected $createdconcepts;

  protected function create($xml, $autoGenerateIdentifiers=false) {
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

  protected function update($xml) {
    $this->client->resetParameters();
    $this->client->setUri(API_BASE_URI . "/concept?");

    $response = $this->client
      ->setEncType('text/xml')
      ->setRawData($xml)
      ->setParameterGet('tenant', TENANT)
      ->setParameterGet('key', API_KEY)
      ->request('PUT');

    return $response;
  }

  protected function deleteConcepts($uris) {
    foreach ($uris as $uri) {
      if ($uri != null) {
        $response = $this->delete($uri);
        if ($response->getStatus() !== 202 && $response->getStatus() !== 200) {
          throw Exception('delete while cleaning up database failed');
        }
      }
    }
  }

  protected function delete($id) {
    $this->client->resetParameters();
    $this->client->setUri(API_BASE_URI . '/concept');
    $response = $this->client
      ->setParameterGet('tenant', TENANT)
      ->setParameterGet('key', API_KEY)
      ->setParameterGet('id', $id)
      ->request('DELETE');
    return $response;
  }

  protected function getAbout($response){
        $dom = new \Zend_Dom_Query();
        $dom->setDocumentXML($response->getBody());
        $description = $dom->queryXpath('/rdf:RDF/rdf:Description'); 
        if ($description->count()<1) {
          throw Exception("rdf:Description element is not declared");
        }
        $resURI = $description->current()->getAttribute("rdf:about");
        if ($resURI === "") {
          throw Exception("No valid uri for SKOS concept");
        }
        return $resURI;
    }
  
}
