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

namespace OpenSkos2\Validator;

use OpenSkos2\Concept;
use OpenSkos2\Rdf\Resource;

abstract class ConceptValidator extends AbstractResourceValidator
{
    /**
     * @param Resource $resource
     * @return bool
     */
    public function validate(Resource $resource)
    {
        if ($resource instanceof Concept) {
            return $this->validateConcept($resource);
        }
        return true;
    }

    /**
     * @param Concept $concept
     * @return bool
     */
    abstract protected function validateConcept(Concept $concept);
}
