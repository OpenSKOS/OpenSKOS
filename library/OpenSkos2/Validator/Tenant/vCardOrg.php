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
                if (count($orgCheck) > 1) {
                    $this->errorMessages[] = 'Wrong xml: there are too many elements of type  ' . vCard::ORG . '. There must be only one.';
                } else {
                    $names = $orgCheck[0]->getProperty(vCard::ORGNAME);
                    if (count($names) === 0) {
                        $this->errorMessages[] = "The institution's name is not given. ";
                    } else {
                        if (count($names) > 1) {
                            $this->errorMessages[] = "Multiple institution names are given. There must be only one. I will validate the first one.";
                        }

                        $name = $names[0]->getValue();
                        $insts = $this->resourceManager->fetchTenantNameUri();
                        if (array_key_exists($name, $insts)) {
                            if ($this->isForUpdate) {
                                if ($insts[$name] !== $resource->getUri()) {
                                    $this->errorMessages[] = 'The institution with the name ' . $name . ' is already registered in the database. ';
                                }
                            } else { //creation, no duplication of names is admissible
                                $this->errorMessages[] = 'The institution with the name ' . $name . ' is already registered in the database. ';
                            }
                        }
                    }
                }
            }
        }
        return (count($this->errorMessages) === 0);
    }

}