<?php

namespace OpenSkos2\Validator\UserRelation;

use OpenSkos2\UserRelation;
use OpenSkos2\Validator\AbstractUserRelationValidator;

class OpenskosUuid extends AbstractUserRelationValidator
{
    protected function validateUserRelation(UserRelation $resource)
    {
        return parent::genericValidate('\CommonProperties\Uuid::validate', $resource);
    }
}
