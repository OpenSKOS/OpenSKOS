<?php

namespace OpenSkos2;

use OpenSKOS_Db_Table_Row_User;

class Authorisation implements \OpenSkos2\Interfaces\Authorisation
{

    private $customAuthorisation;
    private $defaultOn;

    public function __construct($manager)
    {
        $init = $manager->getInitArray();
        $this->defaultOn = $init["custom.default_authorisation"];
        if ($this->defaultOn) {
            $this->customAuthorisation = null;
        } else {
            $this->customAuthorisation = new \OpenSkos2\Custom\Authorisation($manager);
        }
    }

    public function resourceCreateAllowed(OpenSKOS_Db_Table_Row_User $user, \OpenSkos2\Tenant $tenant, $set, $resource)
    {
        if (!$this->defaultOn) {
            $this->customAuthorisation->resourceCreateAllowed($user, $tenant, $set, $resource);
        }
    }

    public function resourceEditAllowed(OpenSKOS_Db_Table_Row_User $user, \OpenSkos2\Tenant $tenant, $set, $resource)
    {

        if (!$this->defaultOn) {
            $this->customAuthorisation->resourceEditAllowed($user, $tenant, $set, $resource);
        }
    }

    public function resourceDeleteAllowed(OpenSKOS_Db_Table_Row_User $user, \OpenSkos2\Tenant $tenant, $set, $resource)
    {
        if (!$this->defaultOn) {
            $this->customAuthorisation->resourceDeleteAllowed($user, $tenant, $set, $resource);
        }
    }

}
