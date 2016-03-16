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

namespace OpenSkos2\Validator\CommonProperties;

use OpenSkos2\Rdf\Resource as RdfResource;
use OpenSkos2\Set;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Validator\DependencyAware\ResourceManagerAwareTrait;

class InSet implements CommonPropertyInterface
{
    use ResourceManagerAwareTrait;
    
    public static function validate(RdfResource $resource)
    {
        $retVal = CommonProperties\SubresourceValidator::validateSubresource($this->getResourceManager(), $resource, OpenSkos::SET, Set::TYPE, true);
        return $retVal;
    }
}