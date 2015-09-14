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

class DuplicateRelatedTest extends \PHPUnit_Framework_TestCase
{

    public function testValidate()
    {
        $validator = new DuplicateRelated();
        $concept = new Concept('http://example.com#1');

        $this->assertTrue($validator->validate($concept));

        $concept->addProperty(SKOS::RELATED, new Uri('http://example.com#Related'));

        $this->assertTrue($validator->validate($concept));

        $concept->addProperty(SKOS::RELATED, new Uri('http://example.com#Related2'));

        $this->assertTrue($validator->validate($concept));


        $concept->addProperty(SKOS::RELATED, new Uri('http://example.com#Related2'));

        $this->assertFalse($validator->validate($concept));
    }
}
