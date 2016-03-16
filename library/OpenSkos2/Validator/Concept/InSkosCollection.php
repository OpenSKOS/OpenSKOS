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

namespace OpenSkos2\Validator\Concept;

use OpenSkos2\Concept;
use OpenSkos2\SkosCollection;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Validator\AbstractConceptValidator;
use OpenSkos2\Validator\CommonProperties\SubresourceValidator;
use OpenSkos2\Validator\DependencyAware\ResourceManagerAware;
use OpenSkos2\Validator\DependencyAware\ResourceManagerAwareTrait;

class InSkosCollection extends AbstractConceptValidator implements ResourceManagerAware
{
    use ResourceManagerAwareTrait;
    
    protected function validateConcept(Concept $concept)
    {
        $retVal = SubresourceValidator::validateSubresource($this->getResourceManager(), $concept, OpenSkos::INSKOSCOLLECTION, SkosCollection::TYPE, false, $this->getErrorMessages());
        return $retVal;
    }
}
