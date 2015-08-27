<?php
/**
 * Created by PhpStorm.
 * User: jsmit
 * Date: 27/08/2015
 * Time: 10:40
 */

namespace OpenSkos2\Validator\Concept;


use OpenSkos2\Concept;
use OpenSkos2\Rdf\Object;

class DuplicateRelatedTest extends \PHPUnit_Framework_TestCase
{

    public function testValidate()
    {
        $validator = new DuplicateRelated();
        $concept = new Concept();

        $this->assertTrue($validator->validate($concept));

        $concept->addProperty(Concept::PROPERTY_RELATED, new Object(Object::TYPE_URI, 'http://example.com#Related'));

        $this->assertTrue($validator->validate($concept));

        $concept->addProperty(Concept::PROPERTY_RELATED, new Object(Object::TYPE_URI, 'http://example.com#Related2'));

        $this->assertTrue($validator->validate($concept));



        $concept->addProperty(Concept::PROPERTY_RELATED, new Object(Object::TYPE_URI, 'http://example.com#Related2'));

        $this->assertFalse($validator->validate($concept));
    }
}
