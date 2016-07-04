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

namespace OpenSkos2;

class FileTest extends \PHPUnit_Framework_TestCase
{
    public function testResources()
    {
        $xml = __DIR__ . '/../../data/concepts.xml';
        $file = new \OpenSkos2\Converter\File($xml);
        $this->assertInstanceOf('\OpenSkos2\Rdf\ResourceCollection', $file->getResources());
    }
    
    public function testConcept()
    {
        $xml = __DIR__ . '/../../data/concept.xml';
        $file = new \OpenSkos2\Converter\File($xml);
        $this->assertInstanceOf('\OpenSkos2\Concept', $file->getResources()[0]);
    }
}
