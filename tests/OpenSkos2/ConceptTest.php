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

namespace OpenSkos2\Concept;

class ConceptTest extends \PHPUnit_Framework_TestCase
{
    public function testGenerateUri()
    {
        $concept = new \OpenSkos2\Concept('');
        $collectionUri = new \OpenSkos2\Rdf\Uri('http://example.com/collection');
        $concept->addProperty(\OpenSkos2\Namespaces\OpenSkos::SET, $collectionUri);
        $uri = $concept->selfGenerateUri();
        $regex = '/^http:\/\/example.com\/collection\/([a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12})$/';
        $this->assertRegExp($regex, $uri);
    }
}
