<?php
/**
 * Created by PhpStorm.
 * User: jsmit
 * Date: 27/08/2015
 * Time: 11:02
 */

namespace OpenSkos2\Validator\Concept;


use OpenSkos2\Concept;
use OpenSkos2\Rdf\Object;

class InSchemeTest extends \PHPUnit_Framework_TestCase
{


    public function testValidate()
    {
        $validator = new InScheme();
        $concept = new Concept();

        //no scheme
        $this->assertFalse($validator->validate($concept));

        $concept->addProperty(Concept::PROPERTY_INSCHEME, new Object(Object::TYPE_URI, 'http://example.com#scheme1'));

        //1 scheme
        $this->assertTrue($validator->validate($concept));

        $concept->addProperty(Concept::PROPERTY_INSCHEME, new Object(Object::TYPE_URI, 'http://example.com#scheme2'));

        //2 schemes
        $this->assertTrue($validator->validate($concept));
    }
}
