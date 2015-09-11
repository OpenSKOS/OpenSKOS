<?php

/*
 * OpenSKOS
 *
 * LICENSE
 *
 * This source file is subject to the GPLv3 license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @category   OpenSKOS
 * @package    OpenSKOS
 * @copyright  Copyright (c) 2015 Picturae (http://www.picturae.com)
 * @author     Picturae
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 */

namespace OpenSkos2\Validator\Concept;

class DuplicateBroaderTest extends \PHPUnit_Framework_TestCase
{

    public function testSerializeLiteral()
    {
        $literal = new \OpenSkos2\Rdf\Literal('123');
        $triple = new \OpenSkos2\Rdf\Serializer\NTriple();
        $serialize = $triple->serialize($literal);
        $this->assertEquals('"123"', $serialize);
    }

    public function testEscapeLiteral()
    {
        $literal = new \OpenSkos2\Rdf\Literal('1"1');
        $triple = new \OpenSkos2\Rdf\Serializer\NTriple();
        $serialize = $triple->serialize($literal);
        $this->assertEquals('"1\\"1"', $serialize);
    }

    public function testURI()
    {
        $uri = new \OpenSkos2\Rdf\Uri('http://example.com/omg');
        $triple = new \OpenSkos2\Rdf\Serializer\NTriple();
        $serialized = $triple->serialize($uri);
        $this->assertEquals('<http://example.com/omg>', $serialized);
    }    
    
    public function testEscapeURI()
    {
        $uri = new \OpenSkos2\Rdf\Uri('http://example.com/omg"');
        $triple = new \OpenSkos2\Rdf\Serializer\NTriple();
        $serialized = $triple->serialize($uri);
        $this->assertEquals('<http://example.com/omg\">', $serialized);
    }
    
    public function testSerializeArray()
    {
        $literal = [
            new \OpenSkos2\Rdf\Literal('123'),
            new \OpenSkos2\Rdf\Literal('134'),
        ];
        $triple = new \OpenSkos2\Rdf\Serializer\NTriple();
        $serialize = $triple->serializeArray($literal);
        $this->assertEquals('"123", "134"', $serialize);
    }

}
