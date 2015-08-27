<?php
/**
 * Created by PhpStorm.
 * User: jsmit
 * Date: 27/08/2015
 * Time: 11:25
 */

namespace OpenSkos2\Validator\Concept;


use OpenSkos2\Concept;
use OpenSkos2\Validator\ConceptValidator;

class RelatedToSelf extends ConceptValidator
{
    /**
     * @param Concept $concept
     * @return bool
     */
    protected function validateConcept(Concept $concept)
    {
        $relationFields = array_merge(Concept::$classes['SemanticRelations'], Concept::$classes['MappingProperties']);

        $ownUri = $concept->getUri();
        foreach ($relationFields as $field) {
            foreach ($concept->getProperty($field) as $object) {
                if ($object->getValue() == $ownUri) {
                    $this->errorMessage ='The concept can not be related to itself.';
                    return false;
                }
            }
        }

        return true;
    }

}