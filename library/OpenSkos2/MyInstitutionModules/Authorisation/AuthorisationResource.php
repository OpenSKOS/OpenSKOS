<?php

namespace OpenSkos2\MyInstitutionModules\Authorisation;

use OpenSKOS_Db_Table_Row_User;


abstract class AuthorisationResource {
    
    public function resourceCreationAllowed(OpenSKOS_Db_Table_Row_User $user, $tenantCode, $tenantUri) {
        return ($user->role === ADMINISRATOR || $user->role === ROOT || $user->role === EDITOR);
    }

    
    public function resourceEditAllowed(OpenSKOS_Db_Table_Row_User $user, $tenantCode, $tenantUri, $resource) {
        return ($user->role === ADMINISRATOR || $user->role === ROOT || $user->role === EDITOR);
    }
    
   
    public function resourceDeleteAllowed(OpenSKOS_Db_Table_Row_User $user, $tenantCode, $tenantUri, $resource) {
        return  ($user->role === ADMINISRATOR || $user->role === ROOT);
    }
}   