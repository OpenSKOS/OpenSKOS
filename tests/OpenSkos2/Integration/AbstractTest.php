<?php

namespace Tests\OpenSkos2\Integration;

abstract class AbstractTest extends \PHPUnit_Framework_TestCase {

  protected static $client;
  protected static $createdconcepts;

  public static function tearDownAfterClass() {
    self::deleteConcepts(self::$createdconcepts);
  }
  protected static function create($xml, $autoGenerateIdentifiers=false) {
    self::$client->resetParameters();
    self::$client->setUri(API_BASE_URI . "/concept?");
    $response = self::$client
      ->setEncType('text/xml')
      ->setRawData($xml)
      ->setParameterGet('tenant', TENANT)
      ->setParameterGet('key', API_KEY)
      ->setParameterGet('autoGenerateIdentifiers', $autoGenerateIdentifiers)
      ->request('POST');
    return $response;
  }  
  
  protected static function update($xml) {
    self::$client->resetParameters();
    self::$client->setUri(API_BASE_URI . "/concept?");

    $response = self::$client
      ->setEncType('text/xml')
      ->setRawData($xml)
      ->setParameterGet('tenant', TENANT)
      ->setParameterGet('key', API_KEY)
      ->request('PUT');

    return $response;
  }
  
  protected static  function deleteConcepts($uris) {
    foreach ($uris as $uri) {
      if ($uri != null) {
        $response = self::delete($uri);
        if ($response->getStatus() !== 202) {
           throw new \Exception('delete '.$uri. ' while cleaning up database failed: '. $response->getStatus(). ", ". $response->getMessage() );
        }
      }
    }
  }

  protected static  function delete($id) {
    self::$client->resetParameters();
    self::$client->setUri(API_BASE_URI . '/concept');
    $response = self::$client
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
          throw new \Exception("No valid uri for SKOS concept");
        }
        return $resURI;
    }
    
    public function getByIndex($list, $index) {
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
}
