<?php

namespace OpenSkos2\Validator\Set;

use OpenSkos2\Validator\GenericProperties\WebPage;
use OpenSkos2\Set;
use OpenSkos2\Validator\AbstractSetValidator;

class OpenskosWebPage extends AbstractSetValidator
{
    protected function validateSet(Set $resource)
    {
       $this->errorMessages = WebPage::validate($resource, $this->isForUpdate);
       return (count($this->errorMessages) === 0);
     }
}
