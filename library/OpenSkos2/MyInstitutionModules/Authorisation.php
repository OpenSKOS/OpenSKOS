<?php

namespace OpenSkos2\MyInstitutionModules;

use OpenSKOS_Db_Table_Row_User;
use OpenSkos2\Concept;
use OpenSkos2\ConceptScheme;
use OpenSkos2\Set;
use OpenSkos2\Tenant;
use OpenSkos2\SkosCollection;
use OpenSkos2\Relation;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Api\Exception\UnauthorizedException;

class Authorisation {

    private $resourceManager;

    public function __construct($manager) {
        $this->resourceManager = $manager;
    }

    public function resourceCreationAllowed(OpenSKOS_Db_Table_Row_User $user, $tenantCode, $tenantUri, $resource) {
        $type = $this->resourceManager->getResourceType();
        switch ($type) {
            case Concept::TYPE:
                return $this->conceptCreationAllowed($user, $tenantCode, $tenantUri, $resource);
            case ConceptScheme::TYPE:
                return $this->conceptSchemeCreationAllowed($user, $tenantCode, $tenantUri, $resource);
            case Set::TYPE:
                return $this->setCreationAllowed($user, $tenantCode, $tenantUri, $resource);
            case Tenant::TYPE:
                return $this->tenantCreationAllowed($user, $tenantCode, $tenantUri, $resource);
            case SkosCollection::TYPE:
                return $this->skosCollectionCreationAllowed($user, $tenantCode, $tenantUri, $resource);
            case Relation::TYPE:
                return $this->relationCreationAllowed($user, $tenantCode, $tenantUri, $resource);
            default:
                return false;
        }
    }

    public function resourceEditAllowed(OpenSKOS_Db_Table_Row_User $user, $tenantCode, $tenantUri, $resource) {
        $type = $this->resourceManager->getResourceType();
        switch ($type) {
            case Concept::TYPE:
                return $this->conceptEditAllowed($user, $tenantCode, $tenantUri, $resource);
            case ConceptScheme::TYPE:
                return $this->conceptSchemeEditAllowed($user, $tenantCode, $tenantUri, $resource);
            case Set::TYPE:
                return $this->setEditAllowed($user, $tenantCode, $tenantUri, $resource);
            case Tenant::TYPE:
                return $this->tenantEditAllowed($user, $tenantCode, $tenantUri, $resource);
            case SkosCollection::TYPE:
                return $this->skosCollectionEditAllowed($user, $tenantCode, $tenantUri, $resource);
            case Relation::TYPE:
                return $this->relationEditAllowed($user, $tenantCode, $tenantUri, $resource);
            default:
                return false;
        }
    }

    public function resourceDeleteAllowed(OpenSKOS_Db_Table_Row_User $user, $tenantCode, $tenantUri, $resource) {
        $type = $this->resourceManager->getResourceType();
        switch ($type) {
            case Concept::TYPE:
                return $this->conceptDeleteAllowed($user, $tenantCode, $tenantUri, $resource);
            case ConceptScheme::TYPE:
                return $this->conceptSchemeDeleteAllowed($user, $tenantCode, $tenantUri, $resource);
            case Set::TYPE:
                return $this->setDeleteAllowed($user, $tenantCode, $tenantUri, $resource);
            case Tenant::TYPE:
                return $this->tenantDeleteAllowed($user, $tenantCode, $tenantUri, $resource);
            case SkosCollection::TYPE:
                return $this->skosCollectionDeleteAllowed($user, $tenantCode, $tenantUri, $resource);
            case Relation::TYPE:
                return $this->relationDeleteAllowed($user, $tenantCode, $tenantUri, $resource);
            default:
                return false;
        }
    }

    private function resourceDeleteAllowedBasic(OpenSKOS_Db_Table_Row_User $user, $tenantCode, $tenantUri, $resource) {
        if ($user->tenant !== $tenantCode) {
            throw new UnauthorizedException('Tenant ' . $tenantCode . ' does not match user given, of tenant ' . $user->tenant, 403);
        }
        return ($user->role === ADMINISRATOR || $user->role === ROOT  || $user->role === EDITOR);
    }

    private function resourceCreationAllowedBasic(OpenSKOS_Db_Table_Row_User $user, $tenantCode, $tenantUri, $resource) {
           if ($user->tenant !== $tenantCode) {
            throw new UnauthorizedException('Tenant ' . $tenantCode . ' does not match user given, of tenant ' . $user->tenant, 403);
        }
        return ($user->role === ADMINISRATOR || $user->role === ROOT || $user->role === EDITOR);
    }

    private function resourceEditAllowedBasic(OpenSKOS_Db_Table_Row_User $user, $tenantCode, $tenantUri, $resource) {
          if ($user->tenant !== $tenantCode) {
            throw new UnauthorizedException('Tenant ' . $tenantCode . ' does not match user given, of tenant ' . $user->tenant, 403);
        }
        return ($user->role === ADMINISRATOR || $user->role === ROOT || $user->role === EDITOR);
    }

    
    private function conceptCreationAllowed(OpenSKOS_Db_Table_Row_User $user, $tenantCode, $tenantUri, $conceptToPost) {
        // the group of users which can post to certain sets, skos-collections or upon certain schemata, can be limited
        if ($user->tenant !== $tenantCode) {
            throw new UnauthorizedException('Tenant ' . $tenantCode . ' does not match user given, of tenant ' . $user->tenant, 403);
        }
        if (!($user->role === EDITOR || $user->role === ADMINISRATOR || $user->role === ROOT)) {
            throw new UnauthorizedException('Your role ' . $user->role . ' does not give you permission to create concepts ', 403);
        }
        return true;
    }

    private function conceptEditAllowed(OpenSKOS_Db_Table_Row_User $user, $tenantCode, $tenantUri, $concept) {

        if ($user->tenant !== $tenantCode) {
            throw new UnauthorizedException('Tenant ' . $tenantCode . ' does not match user given, of tenant ' . $user->tenant, 403);
        }

        $tenantref = current($concept->getProperty(OpenSkos::TENANT));
        if ($tenantUri !== (string) $tenantref) {
            if (TENANTS_AND_SETS_IN_MYSQL) { // $tenantref may be a code for older approaches when tenant is not in the triple store but in my sql database, so compare codes
                if ($tenantCode !== (string) $tenantref) {
                    throw new UnauthorizedException('The concept has tenant ' . (string) $tenantref . ' which does not correspond to the request-s tenant ' . $tenantCode, 403);
                }
            } else {
                throw new UnauthorizedException('The concept has tenant ' . (string) $tenantref . ' which does not correspond to the request-s tenant  ' . $tenantCode . ". You may want to set CHECK_MYSQL to true, if the triple store does not contain " . $tenantCode, 403);
            }
        }

        if (!($user->role === EDITOR || $user->role === ADMINISRATOR || $user->role === ROOT)) {
            throw new UnauthorizedException('Your role ' . $user->role . ' does not give you permission to edit or delete concepts ', 403);
        }
        return true;
    }

    private function conceptDeleteAllowed(OpenSKOS_Db_Table_Row_User $user, $tenantCode, $tenantUri, $concept) {
        $retVal = $this->conceptEditAllowed($user, $tenantCode, $tenantUri, $concept);
        return $retVal;
    }

    private function conceptSchemeCreationAllowed(OpenSKOS_Db_Table_Row_User $user, $tenantCode, $tenantUri, $resource) {
        return $this->resourceCreationAllowedBasic($user, $tenantCode, $tenantUri, $resource);
    }

    private function conceptSchemeEditAllowed(OpenSKOS_Db_Table_Row_User $user, $tenantCode, $tenantUri, $resource) {
        return $this->resourceEditAllowedBasic($user, $tenantCode, $tenantUri, $resource);
    }

    private function conceptSchemeDeleteAllowed(OpenSKOS_Db_Table_Row_User $user, $tenantCode, $tenantUri, $resource) {
        return $this->resourceDeleteAllowedBasic($user, $tenantCode, $tenantUri, $resource);
    }

    private function setCreationAllowed(OpenSKOS_Db_Table_Row_User $user, $tenantCode, $tenantUri, $resource) {
        return $this->resourceCreationAllowedBasic($user, $tenantCode, $tenantUri, $resource);
    }

    private function setEditAllowed(OpenSKOS_Db_Table_Row_User $user, $tenantCode, $tenantUri, $resource) {
        return $this->resourceEditAllowedBasic($user, $tenantCode, $tenantUri, $resource);
    }

    private function setDeleteAllowed(OpenSKOS_Db_Table_Row_User $user, $tenantCode, $tenantUri, $resource) {
        return $this->resourceDeleteAllowedBasic($user, $tenantCode, $tenantUri, $resource);
    }

    private function tenantCreationAllowed(OpenSKOS_Db_Table_Row_User $user, $tenantCode, $tenantUri, $resource) {
        return $this->resourceCreationAllowedBasic($user, $tenantCode, $tenantUri, $resource);
    }

    private function tenantEditAllowed(OpenSKOS_Db_Table_Row_User $user, $tenantCode, $tenantUri, $resource) {
        return $this->resourceEditAllowedBasic($user, $tenantCode, $tenantUri, $resource);
    }

    private function tenantDeleteAllowed(OpenSKOS_Db_Table_Row_User $user, $tenantCode, $tenantUri, $resource) {
        return $this->resourceDeleteAllowedBasic($user, $tenantCode, $tenantUri, $resource);
    }

    private function skosCollectionCreationAllowed(OpenSKOS_Db_Table_Row_User $user, $tenantCode, $tenantUri, $resource) {
        return $this->resourceCreationAllowedBasic($user, $tenantCode, $tenantUri, $resource);
    }

    private function skosCollectionEditAllowed(OpenSKOS_Db_Table_Row_User $user, $tenantCode, $tenantUri, $resource) {
        return $this->resourceEditAllowedBasic($user, $tenantCode, $tenantUri, $resource);
    }

    private function skosCollectionDeleteAllowed(OpenSKOS_Db_Table_Row_User $user, $tenantCode, $tenantUri, $resource) {
        return $this->resourceDeleteAllowedBasic($user, $tenantCode, $tenantUri, $resource);
    }

    private function relationCreationAllowed(OpenSKOS_Db_Table_Row_User $user, $tenantCode, $tenantUri, $resource) {
        return $this->resourceCreationAllowedBasic($user, $tenantCode, $tenantUri, $resource);
    }

    private function relationEditAllowed(OpenSKOS_Db_Table_Row_User $user, $tenantCode, $tenantUri, $resource) {
        return $this->resourceEditAllowedBasic($user, $tenantCode, $tenantUri, $resource);
    }

    private function relationDeleteAllowed(OpenSKOS_Db_Table_Row_User $user, $tenantCode, $tenantUri, $resource) {
        return $this->resourceDeleteAllowedBasic($user, $tenantCode, $tenantUri, $resource);
    }

}
