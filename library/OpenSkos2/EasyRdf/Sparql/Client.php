<?php

/**
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

namespace OpenSkos2\EasyRdf\Sparql;

use EasyRdf\Graph;

class Client extends \EasyRdf\Sparql\Client
{
    /**
     * Deletes the resource and inserts it with the new data.
     * @param string $uri The resource id
     * @param Graph|string $data The insert data
     */
    public function replace($uri, $data)
    {
        $query = 'DELETE WHERE {<' . $uri . '> ?predicate ?object};';
        $query .= PHP_EOL;
        $query .= 'INSERT DATA {' . $this->convertToTriples($data) . '}';
        
        $this->update($query);
    }
}
