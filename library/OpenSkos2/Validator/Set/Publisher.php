<?php

namespace OpenSkos2\Validator\Set;


use OpenSkos2\Set;
use OpenSkos2\Validator\AbstractSetValidator;
use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Namespaces\Org;

class Publisher extends AbstractSetValidator
{
    
     //validateProperty(RdfResource $resource, $propertyUri, $isRequired, $isSingle, $isUri, $isBoolean, $isUnique,  $type)
    protected function validateSet(Set $resource){
       $firstRound = $this->validateProperty($resource, DcTerms::PUBLISHER, true, true, false, false, Org::FORMALORG);
       $tenantUris = $resource ->getProperty(DcTerms::PUBLISHER);
       $errorsBeforeSecondRound = count($this->errorMessages);
       foreach ($tenantUris as $tenantUri) {
           if ($tenantUri->getUri() !== $this->tenant->getUri()) {
              $this->errorMessages[]="The given publisher " . $tenantUri->getUri()."  does not correspond to the tenant code given in the parameter request which refers to the tenant with uri ". $this->tenant->getUri() . "." ;
           }
       }
       $errorsAfterSecondRound = count($this->errorMessages);
       return $firstRound && ($errorsBeforeSecondRound === $errorsAfterSecondRound);
    }
}
