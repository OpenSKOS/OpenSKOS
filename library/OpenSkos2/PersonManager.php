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

namespace OpenSkos2;

use OpenSkos2\Rdf\ResourceManager;
use OpenSkos2\Rdf\Literal;
use OpenSkos2\Namespaces\Foaf;

class PersonManager extends ResourceManager
{
    /**
     * What is the basic resource for this manager.
     * @var string NULL means any resource.
     */
    protected $resourceType = Person::TYPE;
    
    /**
     * Fetches a person by it's name
     * @param string $name
     * @return Person | null
     */
    public function fetchByName($name)
    {
        $result = $this->fetch([
            Foaf::NAME => new Literal($name)
        ]);

        if (count($result) == 0) {
            return null;
        }

        if (count($result) > 1) {
            throw new \RuntimeException(
                'More than 1 users use the name "' . $name . '"'
            );
        }

        return $result[0];
    }
}
