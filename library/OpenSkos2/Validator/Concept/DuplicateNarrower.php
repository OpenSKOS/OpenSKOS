<?php
/**
 * Created by PhpStorm.
 * User: jsmit
 * Date: 27/08/2015
 * Time: 10:02
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