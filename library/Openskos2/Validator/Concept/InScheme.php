<?php
/**
 * Created by PhpStorm.
 * User: jsmit
 * Date: 27/08/2015
 * Time: 10:57
 */

namespace OpenSkos2\Validator\Concept;


use OpenSkos2\Concept;
use OpenSkos2\Validator\ConceptValidator;

class InScheme extends ConceptValidator
{
    /**
     * @param Concept $concept
     * @return bool
     */
    protected function validateConcept(Concept $concept)
    {
        if (!count($concept->getProperty(Concept::PROPERTY_INSCHEME))) {
            $this->errorMessage = 'The concept must be included in at least one scheme.';
            return false;
        }
        return true;
    }

}