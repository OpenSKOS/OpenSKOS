<?php

namespace OpenSkos2;

use OpenSKOS_Db_Table_Row_User;
use OpenSkos2\ConfigOptions;

class Authorisation implements \OpenSkos2\Interfaces\Authorisation
{

    private $customAuthorisation;

    public function __construct($manager)
    {
        if (ConfigOptions::DEFAULT_AUTHORISATION) {
            $this->customAuthorisation = null;
        } else {
            $this->customAuthorisation = new \OpenSkos2\Custom\Authorisation($manager);
        }
    }

    public function resourceCreationAllowed(OpenSKOS_Db_Table_Row_User $user, $tenant, $set, $resource)
    {
        if (ConfigOptions::DEFAULT_AUTHORISATION) {
            return true;
        } else {
            return $this->customAuthorisation->resourceCreationAllowed($user, $tenant, $set, $resource);
        }
    }

    public function resourceEditAllowed(OpenSKOS_Db_Table_Row_User $user, $tenant, $set, $resource)
    {

        if (ConfigOptions::DEFAULT_AUTHORISATION) {
            return true;
        } else {
            return $this->customAuthorisation->resourceEditAllowed($user, $tenant, $set, $resource);
        }
    }

    public function resourceDeleteAllowed(OpenSKOS_Db_Table_Row_User $user, $tenant, $set, $resource)
    {
        if (ConfigOptions::DEFAULT_AUTHORISATION) {
            return true;
        } else {
            return $this->customAuthorisation->resourceDeleteAllowed($user, $tenant, $set, $resource);
        }
    }
}
