<?php

namespace OpenSkos2\Interfaces;

use OpenSKOS_Db_Table_Row_User;

interface Authorisation
{

    public function __construct($manager);
    public function resourceCreationAllowed(OpenSKOS_Db_Table_Row_User $user, $tenant, $resource);
    public function resourceEditAllowed(OpenSKOS_Db_Table_Row_User $user, $tenant, $resource);
    public function resourceDeleteAllowed(OpenSKOS_Db_Table_Row_User $user, $tenant, $resource);
}
