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

class UriTest extends \PHPUnit_Framework_TestCase
{
    public function testUri()
    {
        $value = 'http://example.com/1';
        $uri = new Uri($value);
        $this->assertEquals($value, $uri->getUri());
    }

    public function testInvaldUri()
    {
        $this->setExpectedException('\OpenSkos2\Rdf\Exception\InvalidUriException');
        $value = '12345';
        new Uri($value);
    }
}
