<?php

namespace OpenSkos2\MyInstitutionModules\Authorisation;

use OpenSKOS_Db_Table_Row_User;


class AuthorisationTenant extends AuthorisationResource{
    
    public function resourceCreationAllowed(OpenSKOS_Db_Table_Row_User $user, $tenantCode, $tenantUri) {
        return parent::resourceCreationAllowed($user, $tenantCode, $tenantUri);
    }

    
    public function resourceEditAllowed(OpenSKOS_Db_Table_Row_User $user, $tenantCode, $tenantUri,  $resource) {
        return parent::resourceEditAllowed($user, $tenantCode, $tenantUri,  $resource);
    }
    
   
    public function resourceDeleteAllowed(OpenSKOS_Db_Table_Row_User $user,$tenantCode, $tenantUri,  $resource) {
        return parent::resourceDeleteAllowed($user, $tenantCode, $tenantUri,  $resource);
    }
}   