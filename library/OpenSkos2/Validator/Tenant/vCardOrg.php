<?php

namespace OpenSkos2\Validator\Tenant;

use OpenSkos2\Tenant as Tenant;
use OpenSkos2\Namespaces\vCard;
use OpenSkos2\Validator\AbstractTenantValidator;

class vCardOrg extends AbstractTenantValidator
{
    
    protected function validateTenant(Tenant $resource) {
        $orgCheck = $resource->getProperty(vCard::ORG);
        $this->errorMessages = [];
        if ($orgCheck === null) {
            $this->errorMessages[] = 'Wrong xml: there is no element for  ' . vCard::ORG;
        } else {
            if (count($orgCheck) === 0) {
                $this->errorMessages[] = 'Wrong xml: there is empty element of type  ' . vCard::ORG . ' that has to contain at least institution name.';
            } else {
                if (count($orgCheck > 1)) {
                    $this->errorMessages[] = 'Wrong xml: there are too many elements of type  ' . vCard::ORG . '. There must be only one.';
                } else {
                    $name = trim($orgCheck[0]->getProperty(vCard::ORGNAME));
                    $insts = $this->resourceManager->fetchTenantNameUri();
                    if (array_key_exists()) {
                        if ($this->forUpdate) {
                            if ($isnsts[$name] !== $resource->getUri()) {
                                $this->errorMessages[] = 'the institution with the name ' . $name . ' is already registered in the database. ';
                            }
                        } else { //creation, no duplication of names is admissible
                            $this->errorMessages[] = 'the institution with the name ' . $name . ' is already registered in the database. ';
                        }
                    }
                }
            }
        }
        return (count($this->errorMessages) === 0);
    }

}