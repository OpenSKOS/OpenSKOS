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
use OpenSkos2\Validator\ConceptValidator;

class DuplicateNarrower extends ConceptValidator
{
    /**
     * @param Concept $concept
     * @return bool
     */
    protected function validateConcept(Concept $concept)
    {
        $narrowerTerms = $concept->getProperty(Concept::PROPERTY_NARROWER);

        $loopedConcepts = [];
        foreach ($narrowerTerms as $narrowerTerm) {
            if (isset($loopedConcepts[$narrowerTerm->getValue()])) {
                $this->errorMessage = "Narrower term {$narrowerTerm->getValue()} is defined more than once";
                return false;
            }
            $loopedConcepts[$narrowerTerm->getValue()] = true;
        }

        return true;
    }

}