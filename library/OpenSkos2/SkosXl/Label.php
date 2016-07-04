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
namespace OpenSkos2\SkosXl;

use OpenSkos2\Rdf\Resource;
use OpenSkos2\Namespaces\SkosXl;
use Rhumsaa\Uuid\Uuid;

class Label extends Resource
{
    const TYPE = SkosXl::LABEL;
    
    /**
     * Generates label uri
     * @return string
     */
    public static function generateUri()
    {
        // @TODO
        return 'http://openskos.org/label/' . Uuid::uuid4();
    }
}
