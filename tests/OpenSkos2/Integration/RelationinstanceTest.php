<?php

namespace Tests\OpenSkos2\Integration;

require_once 'AbstractTest.php';

use OpenSkos2\Roles;

class RelationinstanceTest extends AbstractTest
{

    private static $prefLabel1;
    private static $uuid1;
    private static $about1;
    private static $prefLabel2;
    private static $uuid2;
    private static $about2;

    
    public function setUp()
    {
        self::$init = parse_ini_file(__DIR__ . '/../../../application/configs/application.ini');
        
        self::$createdresourses = array();
        self::$client = new \Zend_Http_Client();
        self::$client->SetHeaders(array(
            'Accept' => 'text/html,application/xhtml+xml,application/xml',
            'Content-Type' => 'text/xml',
            'Accept-Language' => 'nl,en-US,en',
            'Accept-Encoding' => 'gzip, deflate',
            'Connection' => 'keep-alive')
        );
        $result1 = $this->createTestConcept(API_KEY_EDITOR);
        if ($result1['response']->getStatus() === 201) {
            $result2 = $this->createTestConcept(API_KEY_EDITOR);
            if ($result2['response']->getStatus() === 201) {
                self::$prefLabel1 = $result1['prefLabel'];
                self::$uuid1 = $result1['uuid'];
                self::$about1 = $result1['about'];
                self::$prefLabel2 = $result2['prefLabel'];
                self::$uuid2 = $result2['uuid'];
                self::$about2 = $result2['about'];
            } else {
                var_dump($result2['response']->getBody());
                self::delete(self::$about1, API_KEY_ADMIN, 'concept'); // delete the first concept
                throw new \Exception('Cannot create the second test concept: ' . $result2['response']->getStatus() . '; ' . $result2['response']->getMessage());
            }
        } else {
            var_dump($result1['response']->getBody());
            throw new \Exception('Cannot create the first test concept: ' . $result1['response']->getStatus() . '; ' . $result1['response']->getMessage());
        }
    }

    public static function tearDownAfterClass()
    {
        
    }

    public function tearDown()
    {
        self::delete(self::$about1, API_KEY_ADMIN, 'concept');
        self::delete(self::$about2, API_KEY_ADMIN, 'concept');
    }

    public function testCreateRelation()
    {
        print "\n" . "Test: create relation 1 related 2 via text body";
        $body = 'concept=' . self::$about1 . '&type=http://www.w3.org/2004/02/skos/core#narrower&related=' . self::$about2;
        $response = $this->createRelationTriple($body);
        $this->AssertEquals(200, $response->getStatus(), $response->getHeader('X-Error-Msg'));
// todo: add assertions
    }

    private function createRelationTriple($body)
    {
        self::$client->resetParameters();
        self::$client->setUri(API_BASE_URI . "/relation");
        self::$client->setConfig(array(
            'maxredirects' => 2,
            'timeout' => 30));
        self::$client->SetHeaders(array(
            'Accept' => 'application/json',
            'Content-Type' => 'appliction/json',
            'Accept-Language' => 'nl,en-US,en',
            'Accept-Encoding' => 'gzip, deflate',
            'Connection' => 'keep-alive')
        );
        $response = self::$client
            ->setRawData($body)
            ->setParameterGet('tenant', TENANT_CODE)
            ->setParameterGet('key', API_KEY_EDITOR);
        if (self::$init["optional.backward_compatible"]) {
            self::$client->setParameterGet('collection', SET_CODE);
        } else {
            self::$client->setParameterGet('set', SET_CODE);
        };
        $response = self::$client->request('POST');
        return $response;
    }

}
