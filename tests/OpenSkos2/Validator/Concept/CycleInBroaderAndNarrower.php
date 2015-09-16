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

use OpenSkos2\Concept;
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Rdf\Uri;

class CycleBroaderAndNarrowerTest extends \PHPUnit_Framework_TestCase
{

    public function testValidate()
    {
        $validator = new \OpenSkos2\Validator\Concept\CycleBroaderAndNarrower();
        
        $concept = new Concept('http://example.com#1');
        
        $concept->addProperty(Skos::BROADER, new Uri('http://example.com#broader'));

        $this->assertTrue($validator->validate($concept));

        $concept->addProperty(Skos::NARROWER, new Uri('http://example.com#broader'));

        $this->assertFalse($validator->validate($concept));
    }
}
