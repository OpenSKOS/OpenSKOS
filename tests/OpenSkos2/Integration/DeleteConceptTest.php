<?php

namespace Tests\OpenSkos2\Integration;

require_once 'AbstractTest.php';

class DeleteConceptTest extends AbstractTest
{

    private static $prefLabel;
    private static $altLabel;
    private static $hiddenLabel;
    private static $notation;
    private static $uuid;
    private static $about;
    private static $xml;

    public function setUp()
    {
        self::$client = new \Zend_Http_Client();
        self::$client->SetHeaders(array(
            'Accept' => 'text/html,application/xhtml+xml,application/xml',
            'Content-Type' => 'text/xml',
            'Accept-Language' => 'nl,en-US,en',
            'Accept-Encoding' => 'gzip, deflate',
            'Connection' => 'keep-alive')
        );
        $result = $this->createTestConcept(API_KEY_EDITOR, 'candidate');
        if ($result['response']->getStatus() === 201) {
            self::$prefLabel = $result['prefLabel'];
            self::$altLabel = $result['altLabel'];
            self::$hiddenLabel = $result['hiddenLabel'];
            self::$notation = $result['notation'];
            self::$uuid = $result['uuid'];
            self::$about = $result['about'];
            self::$xml = $result['xml'];
        } else {
            shell_exec("php " . SOURCE_DIR . "/tools/concept.php --key=" . API_KEY_ADMIN . " --tenant=" . TENANT_CODE . "  delete");
            throw new \Exception('Cannot create a test concept: ' . $result['response']->getStatus() . "\n " . $result['response']->getMessage(). "\n " . $result['response']->getBody());
        }
    }

    // delete all created in this test concepts (clean garbage)
    public static function tearDownAfterClass()
    {
        shell_exec("php " . SOURCE_DIR . "/tools/concept.php --key=" . API_KEY_ADMIN . " --tenant=" . TENANT_CODE . "  delete");
    }

    public function testDeleteCandidateByAdmin()
    {
        print "\n deleting concept with candidate status by admin... \n";
        $response = self::delete(self::$about, API_KEY_ADMIN, 'concept');
        $this->AssertEquals(202, $response->getStatus(), $response->getHeader("X-Error-Msg"));
        self::$client->setUri(API_BASE_URI . '/concept?id=' . self::$uuid);
        $checkResponse = self::$client->request('GET');
        $this->AssertEquals(410, $checkResponse->getStatus(), 'Admin was not able to delete an approved concept or something else went wrong. Getting that concept gives status ' . $checkResponse->getStatus());
    }

    public function testDeleteCandidatebyOwner()
    { // TODO ///
        print "\n deleting concept with candidate status by the owner-deitor... \n";
        $response = self::delete(self::$about, API_KEY_EDITOR, 'concept');
        $this->AssertEquals(202, $response->getStatus(), $response->getHeader("X-Error-Msg"));
        self::$client->setUri(API_BASE_URI . '/concept?id=' . self::$uuid);
        $checkResponse = self::$client->request('GET');
        $this->AssertEquals(410, $checkResponse->getStatus(), 'Admin was not able to delete an approved concept or something else went wrong. Getting that concept gives status ' . $checkResponse->getStatus());
    }

    public function testDeleteCandidateByGuest()
    {
        if (!DEFAULT_AUTHORISATION) {
            print "\n deleting concept with candidate status by guest...\n";
            $response = self::delete(self::$about, API_KEY_GUEST, 'concept');
            $this->AssertEquals(403, $response->getStatus(), $response->getHeader("X-Error-Msg"));
        }
    }

    public function testDeleteApprovedByAdmin()
    {
        print "\n deleting concept with approved status by admin ...\n";
        self::update(self::$xml, API_KEY_EDITOR, 'concept'); // updating will make the status "approved" 
        $response = self::delete(self::$about, API_KEY_ADMIN, 'concept');
        $this->AssertEquals(202, $response->getStatus(), $response->getHeader("X-Error-Msg"));
        self::$client->setUri(API_BASE_URI . '/concept?id=' . self::$uuid);
        $checkResponse = self::$client->request('GET');
        $this->AssertEquals(410, $checkResponse->getStatus(), 'Admin was not able to delete an approved concept or something else went wrong. Getting that concept gives status ' . $checkResponse->getStatus());
    }

    public function testDeleteApprovedByOwner()
    {
        print "\n deleting concept with approved status by an owner-editor ...";
        self::update(self::$xml, API_KEY_EDITOR, 'concept'); // updating will make the status "approved" 
        $response = self::delete(self::$about, API_KEY_EDITOR, 'concept');
        $this->AssertEquals(202, $response->getStatus(), $response->getHeader("X-Error-Msg"));
    }

    public function testDeleteApprovedByGuest()
    {
        print "\n deleting concept with approved status by a guest ...";
        self::update(self::$xml, API_KEY_EDITOR, 'concept'); // updating will make the status "approved" 
        $response = self::delete(self::$about, API_KEY_GUEST, 'concept');
        if (DEFAULT_AUTHORISATION) {
            $this->AssertEquals(202, $response->getStatus(), $response->getMessage());
        } else {
            $this->AssertEquals(403, $response->getStatus(), $response->getHeader("X-Error-Msg"));
        }
    }

}
