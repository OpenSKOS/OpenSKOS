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

class DuplicateRelated extends ConceptValidator
{
    /**
     * @param Concept $concept
     * @return bool
     */
    protected function validateConcept(Concept $concept)
    {
        $relatedTerms = $concept->getProperty(Concept::PROPERTY_RELATED);

        $loopedConcepts = [];
        foreach ($relatedTerms as $relatedTerm) {
            if (isset($loopedConcepts[$relatedTerm->getValue()])) {
                $this->errorMessage = "Related term {$relatedTerm->getValue()} is defined more than once";
                return false;
            }
            $loopedConcepts[$relatedTerm->getValue()] = true;
        }

        return true;
    }

}