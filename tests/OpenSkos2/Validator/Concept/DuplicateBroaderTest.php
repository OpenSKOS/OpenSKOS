<?php
/**
 * Created by PhpStorm.
 * User: jsmit
 * Date: 27/08/2015
 * Time: 10:40
 */

namespace OpenSkos2\Validator\Concept;

use OpenSkos2\Concept;
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Rdf\Uri;

class DuplicateBroaderTest extends \PHPUnit_Framework_TestCase
{

    public function testValidate()
    {
        $validator = new DuplicateBroader();
        $concept = new Concept('http://example.com#1');

        $this->assertTrue($validator->validate($concept));

        $concept->addProperty(Skos::BROADER, new Uri('http://example.com#broader'));

        $this->assertTrue($validator->validate($concept));

        $concept->addProperty(Skos::BROADER, new Uri('http://example.com#broader2'));

        $this->assertTrue($validator->validate($concept));


        $concept->addProperty(Skos::BROADER, new Uri('http://example.com#broader2'));

        $this->assertFalse($validator->validate($concept));
    }
}
