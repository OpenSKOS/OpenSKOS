<?php

/*
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
use OpenSkos2\Validator\AbstractConceptValidator;

class NoEmptyValues extends AbstractConceptValidator
{
    /**
     * Validate if the properties which are normally translated have a language
     * @param Concept $concept
     * @return bool
     */
    protected function validateConcept(Concept $concept)
    {
        $isValid = true;
        
        foreach ($concept->getProperties() as $property => $values) {
            foreach ($values as $value) {
                if ($value->isEmpty()) {
                    $this->errorMessages[] = sprintf(
                        'Property "%s" has an empty value',
                        $property
                    );
                    $isValid = false;
                }
            }
        }
        
        return $isValid;
    }
}
