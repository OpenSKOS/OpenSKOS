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

namespace OpenSkos2\Sparql;

class Escape
{
    
    /**
     * Escape literal to use in sparql
     *
     * @param string $literal
     */
    public static function escapeLiteral($literal)
    {
        $ol = new \OpenSkos2\Rdf\Literal($literal);
        return (new \OpenSkos2\Rdf\Serializer\NTriple())->serialize($ol);
    }
    
    /**
     * Escape uri to use in sparql
     *
     * @param string $uri
     * @return string
     */
    public static function escapeUri($uri)
    {
        $ouri = new \OpenSkos2\Rdf\Uri($uri);
        return (new \OpenSkos2\Rdf\Serializer\NTriple())->serialize($ouri);
    }
}
