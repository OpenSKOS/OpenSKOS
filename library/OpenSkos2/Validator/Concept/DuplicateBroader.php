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

class DuplicateBroader extends ConceptValidator
{
    /**
     * @param Concept $concept
     * @return bool
     */
    protected function validateConcept(Concept $concept)
    {
        $broaderTerms = $concept->getProperty(Concept::PROPERTY_BROADER);

        $loopedConcepts = [];
        foreach ($broaderTerms as $broaderTerm) {
            if (isset($loopedConcepts[$broaderTerm->getValue()])) {
                $this->errorMessage = "Broader term {$broaderTerm->getValue()} is defined more than once";
                return false;
            }
            $loopedConcepts[$broaderTerm->getValue()] = true;
        }

        return true;
    }

}