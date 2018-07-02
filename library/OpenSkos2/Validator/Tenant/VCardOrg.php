<?php

namespace OpenSkos2\Validator\Tenant;

use OpenSkos2\Tenant as Tenant;
use OpenSkos2\Namespaces\VCard;
use OpenSkos2\Validator\AbstractTenantValidator;

class VCardOrg extends AbstractTenantValidator
{

    protected function validateTenant(Tenant $resource)
    {
        $orgCheck = $resource->getProperty(VCard::ORG);
        $this->errorMessages = [];
        if ($orgCheck === null) {
            $this->errorMessages[] = 'Wrong xml: there is no element for  ' . VCard::ORG;
        } else {
            if (count($orgCheck) === 0) {
                $this->errorMessages[] = 'Wrong xml: there is empty element of type  ' .
                    VCard::ORG . ' that has to contain at least institution name.';
            } else {
                if (count($orgCheck) > 1) {
                    $this->errorMessages[] = 'Wrong xml: there are too many elements of type  ' .
                        VCard::ORG . '. There must be only one.';
                } else {
                    $names = $orgCheck[0]->getProperty(VCard::ORGNAME);
                    if (count($names) === 0) {
                        $this->errorMessages[] = "The institution's name is not given. ";
                    } else {
                        if (count($names) > 1) {
                            $this->errorMessages[] = "Multiple institution names are given. "
                                . "There must be only one. I will validate the first one.";
                        }

                        $name = $names[0]->getValue();
                        $insts = $this->resourceManager->fetchNameUri();
                        if (array_key_exists($name, $insts)) {
                            if ($this->isForUpdate) {
                                if ($insts[$name] !== $resource->getUri()) {
                                    $this->errorMessages[] = 'The institution with the name ' . $name .
                                        ' has been already registered in the triple store. ';
                                }
                            } else { //creation, no duplication of names is admissible
                                $this->errorMessages[] = 'The institution with the name ' . $name .
                                    ' has been already registered in the triple store. ';
                            }
                        }
                    }
                }
            }
        }
        return (count($this->errorMessages) === 0);
    }
}
