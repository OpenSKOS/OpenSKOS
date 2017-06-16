<?php

namespace OpenSkos2\Interfaces;

use OpenSKOS_Db_Table_Row_User;

interface Authorisation
{

    public function __construct($manager);
    public function resourceCreateAllowed(OpenSKOS_Db_Table_Row_User $user, \OpenSkos2\Tenant $tenant, $set, $resource);
    public function resourceEditAllowed(OpenSKOS_Db_Table_Row_User $user, \OpenSkos2\Tenant $tenant, $set, $resource);
    public function resourceDeleteAllowed(OpenSKOS_Db_Table_Row_User $user, \OpenSkos2\Tenant $tenant, $et, $resource);
}
