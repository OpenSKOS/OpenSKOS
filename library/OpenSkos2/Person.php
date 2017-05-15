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

use OpenSkos2\Namespaces\Rdf;
use OpenSkos2\Namespaces\Foaf;
use OpenSkos2\Rdf\Resource;
use OpenSkos2\Rdf\Uri;

class Person extends Resource
{

    const TYPE = Foaf::PERSON;

    /**
     * Resource constructor.
     * @param string $uri
     */
    public function __construct($uri = null)
    {
        parent::__construct($uri);
        $this->addProperty(Rdf::TYPE, new Uri(self::TYPE));
    }

    /**
     * Gets name of person
     * @param string $language
     * @return string
     * @throws \Exception
     */
    public function getCaption($language = null)
    {
        return $this->getPropertyFlatValue(Foaf::NAME, $language);
    }

    // override for a concerete resources
    public function addMetadata($existingResource, $userUri, $tenant, $set)
    {
        
    }
}
