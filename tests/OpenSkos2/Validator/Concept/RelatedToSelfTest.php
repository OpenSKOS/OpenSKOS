<?php
/**
 * Created by PhpStorm.
 * User: jsmit
 * Date: 27/08/2015
 * Time: 11:32
 */

namespace OpenSkos2\Validator\Concept;


use OpenSkos2\Concept;
use OpenSkos2\Rdf\Object;

class RelatedToSelfTest extends \PHPUnit_Framework_TestCase
{

    public function testValidate()
    {
        $validator = new RelatedToSelf();
        $concept = new Concept();
        $concept->setUri('http://example.com#concept1');

        $this->assertTrue($validator->validate($concept));

        $concept->addProperty(Concept::PROPERTY_NARROWER, new Object(Object::TYPE_URI, 'http://example.com#concept2'));
        $this->assertTrue($validator->validate($concept));


        $concept->addProperty(Concept::PROPERTY_NARROWER, new Object(Object::TYPE_URI, 'http://example.com#concept1'));
        $this->assertFalse($validator->validate($concept));
    }
}
