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

namespace OpenSkos2\Validator\SkosCollection;

use OpenSkos2\Rdf\Resource as RdfResource;
use OpenSkos2\Validator\DependencyAware\ResourceManagerAware;
use OpenSkos2\Validator\DependencyAware\ResourceManagerAwareTrait;
use OpenSkos2\Validator\CommonProperties;

class InSet extends AbstractSkosCollectionValidator implements ResourceManagerAware
{
    use ResourceManagerAwareTrait;
    
    protected function validateSkosCollection(RdfResource $resource)
    {
        $retVal = CommonProperties\InSet::validate($resource, $this->getErrorMessages());
        return $retVal;
    }
}