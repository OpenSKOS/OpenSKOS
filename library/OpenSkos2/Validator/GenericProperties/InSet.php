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

namespace OpenSkos2\Validator\GenericProperties;

use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Namespaces\Dcmi;
use OpenSkos2\Rdf\Resource as RdfResource;
use OpenSkos2\Validator\GenericProperties\AbstractProperty;

class InSet extends AbstractProperty
{
     // parent::validate(RdfResource $resource, $propertyUri, $isRequired, $isSingle, $isUri, $isBoolean, $isUnique,  $type, $isForUpdate)
    
    public static function validate(RdfResource $resource)
    {
        $retVal = parent::validate($resource, OpenSkos::SET, false, true, true, false, false, Dcmi::DATASET);
        return $retVal;
    }
}