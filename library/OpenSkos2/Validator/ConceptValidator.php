<?php
/**
 * Created by PhpStorm.
 * User: jsmit
 * Date: 27/08/2015
 * Time: 08:15
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