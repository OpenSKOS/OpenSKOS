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


namespace OpenSkos2\Rdf;

class ResourceTest extends \PHPUnit_Framework_TestCase
{
    public function testAddUniqueProperty()
    {
        $res = new Resource('http://example.com');
        $user = new Literal('john doe');
        $res->addUniqueProperty(\OpenSkos2\Namespaces\DcTerms::CONTRIBUTOR, $user);
        $res->addUniqueProperty(\OpenSkos2\Namespaces\DcTerms::CONTRIBUTOR, $user);
        $contributers = $res->getProperty(\OpenSkos2\Namespaces\DcTerms::CONTRIBUTOR);
        $this->assertEquals(1, count($contributers));
    }
}
