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

use OpenSkos2\Rdf\Resource;
use OpenSkos2\Namespaces\Owl;
use OpenSkos2\Namespaces\Rdf;
use OpenSkos2\Namespaces\Rdfs;
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Rdf\Uri;

class RelationType extends Resource
{

    const TYPE = Owl::OBJECT_PROPERTY;

    public function __construct($uri = null)
    {
        parent::__construct($uri);
        $this->addProperty(Rdf::TYPE, new Uri(self::TYPE));
        $this->addProperty(Rdfs::SUBPROPERTY_OF, new Uri(Skos::RELATED));
    }
}
