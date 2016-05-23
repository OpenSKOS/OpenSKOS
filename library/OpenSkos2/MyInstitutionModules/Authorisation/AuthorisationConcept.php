<?php

namespace OpenSkos2\MyInstitutionModules\Authorisation;

use OpenSKOS_Db_Table_Row_User;
use OpenSkos2\Api\Exception\UnauthorizedException;
use OpenSkos2\Namespaces\OpenSkos;

require_once dirname(__FILE__) .'/../../config.inc.php';

class AuthorisationConcept extends AuthorisationResource{
    
    public function resourceCreationAllowed(OpenSKOS_Db_Table_Row_User $user, $tenantCode, $tenantUri) {
        if ($user->tenant !== $tenantCode) {
            throw new UnauthorizedException('Tenant ' . $tenantCode . ' does not match user given, of tenant ' .  $user->tenant, 403);
        }
        return ($user->role === EDITOR || $user->role === ADMINISRATOR || $user->role === ROOT);
    }
    

    public function resourceEditAllowed(OpenSKOS_Db_Table_Row_User $user, $tenantCode, $tenantUri,   $concept) {
       
        if ($user->tenant !== $tenantCode) {
            throw new UnauthorizedException('Tenant ' . $tenantCode . ' does not match user given, of tenant ' .  $user->tenant, 403);
        }
        
        $tenantref = current($concept->getProperty(OpenSkos::TENANT));
        if ($tenantUri !== (string) $tenantref) {
            if (CHECK_MYSQL) { // $tenantref may be a code for older approaches when tenant is not in the triple store but in my sql database, so compare codes
               if ($tenantCode !== (string) $tenantref) {
                  throw new UnauthorizedException('The concept has tenant ' . (string) $tenantref . ' which is not the uri of the tenant with the code ' . $tenantCode, 403);
                 } 
            } else {
                throw new UnauthorizedException('The concept has tenant ' . (string) $tenantref . ' which is not the uri of the tenant with the code ' . $tenantCode, 403);
            }
        }

        if (!($user->role === EDITOR || $user->role === ADMINISRATOR || $user->role === ROOT) ) {
           throw new UnauthorizedException('Your role ' . $user->role . ' does not give you permission to edit or delete concepts ', 403); 
        }
        return true;
    }
    
    public function resourceDeleteAllowed( OpenSKOS_Db_Table_Row_User $user, $tenantCode, $tenantUri,  $concept) {
        $retVal = $this->resourceEditAllowed($user, $tenantCode, $tenantUri, $concept);
        return $retVal;
    }
    
}   

