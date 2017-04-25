<?php

namespace OpenSkos2\Validator;

use OpenSkos2\ConceptScheme;
use OpenSkos2\Rdf\Resource as RdfResource;

abstract class AbstractConceptSchemeValidator extends AbstractResourceValidator
{

    public function __construct($referencecheckOn = true)
    {
        $this->resourceType = ConceptScheme::TYPE;
        $this->referenceCheckOn = $referencecheckOn;
    }

    public function validate(RdfResource $resource)
    {
        if ($resource instanceof ConceptScheme) {
            return $this->validateSchema($resource);
        }
        return false;
    }

    abstract protected function validateSchema(ConceptScheme $schema);
}
