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
use OpenSkos2\Namespaces\Org;
use OpenSkos2\Api\Exception\UnauthorizedException;

class Authorisation {

    private $resourceManager;

    public function __construct($manager) {
        $this->resourceManager = $manager;
    }

    public function resourceCreationAllowed(OpenSKOS_Db_Table_Row_User $user, $tenantCode, $resource) {
        $type = $this->resourceManager->getResourceType();
        switch ($type) {
            case Concept::TYPE:
                return $this->conceptCreationAllowed($user, $tenantCode, $resource);
            case ConceptScheme::TYPE:
                return $this->conceptSchemeCreationAllowed($user, $tenantCode, $resource);
            case Set::TYPE:
                return $this->setCreationAllowed($user, $tenantCode, $resource);
            case Tenant::TYPE:
                return $this->tenantCreationAllowed($user, $tenantCode, $resource);
            case SkosCollection::TYPE:
                return $this->skosCollectionCreationAllowed($user, $tenantCode, $resource);
            case Relation::TYPE:
                return $this->relationCreationAllowed($user, $tenantCode, $resource);
            default:
                return false;
        }
    }

    public function resourceEditAllowed(OpenSKOS_Db_Table_Row_User $user, $tenantCode, $resource) {
        $type = $this->resourceManager->getResourceType();
        switch ($type) {
            case Concept::TYPE:
                return $this->conceptEditAllowed($user, $tenantCode, $resource);
            case ConceptScheme::TYPE:
                return $this->conceptSchemeEditAllowed($user, $tenantCode, $resource);
            case Set::TYPE:
                return $this->setEditAllowed($user, $tenantCode, $resource);
            case Tenant::TYPE:
                return $this->tenantEditAllowed($user, $tenantCode, $resource);
            case SkosCollection::TYPE:
                return $this->skosCollectionEditAllowed($user, $tenantCode, $resource);
            case Relation::TYPE:
                return $this->relationEditAllowed($user, $tenantCode, $resource);
            default:
                return false;
        }
    }

    public function resourceDeleteAllowed(OpenSKOS_Db_Table_Row_User $user, $tenantCode, $resource) {
        $type = $this->resourceManager->getResourceType();
        switch ($type) {
            case Concept::TYPE:
                return $this->conceptDeleteAllowed($user, $tenantCode, $resource);
            case ConceptScheme::TYPE:
                return $this->conceptSchemeDeleteAllowed($user, $tenantCode, $resource);
            case Set::TYPE:
                return $this->setDeleteAllowed($user, $tenantCode, $resource);
            case Tenant::TYPE:
                return $this->tenantDeleteAllowed($user, $tenantCode, $resource);
            case SkosCollection::TYPE:
                return $this->skosCollectionDeleteAllowed($user, $tenantCode, $resource);
            case Relation::TYPE:
                return $this->relationDeleteAllowed($user, $tenantCode, $resource);
            default:
                return false;
        }
    }

    private function resourceDeleteAllowedBasic(OpenSKOS_Db_Table_Row_User $user, $tenantCode, $resource) {
        if ($user->tenant !== $tenantCode) {
            throw new UnauthorizedException('Tenant ' . $tenantCode . ' does not match user given, of tenant ' . $user->tenant, 403);
        }
        return ($user->role === ADMINISTRATOR || $user->role === ROOT  || $user->role === EDITOR);
    }

    private function resourceCreationAllowedBasic(OpenSKOS_Db_Table_Row_User $user, $tenantCode, $resource) {
           if ($user->tenant !== $tenantCode) {
            throw new UnauthorizedException('Tenant ' . $tenantCode . ' does not match user given, of tenant ' . $user->tenant, 403);
        }
        return ($user->role === ADMINISTRATOR || $user->role === ROOT || $user->role === EDITOR);
    }

    private function resourceEditAllowedBasic(OpenSKOS_Db_Table_Row_User $user, $tenantCode, $resource) {
          if ($user->tenant !== $tenantCode) {
            throw new UnauthorizedException('Tenant ' . $tenantCode . ' does not match user given, of tenant ' . $user->tenant, 403);
        }
        return ($user->role === ADMINISTRATOR || $user->role === ROOT || $user->role === EDITOR);
    }

    
    private function conceptCreationAllowed(OpenSKOS_Db_Table_Row_User $user, $tenantCode, $conceptToPost) {
        // the group of users which can post to certain sets, skos-collections or upon certain schemata, can be limited
        if ($user->tenant !== $tenantCode) {
            throw new UnauthorizedException('Tenant ' . $tenantCode . ' does not match user given, of tenant ' . $user->tenant, 403);
        }
        if (!($user->role === EDITOR || $user->role === ADMINISTRATOR || $user->role === ROOT)) {
            throw new UnauthorizedException('Your role ' . $user->role . ' does not give you permission to create concepts ', 403);
        }
        return true;
    }

    private function conceptEditAllowed(OpenSKOS_Db_Table_Row_User $user, $tenantCode, $concept) {
        if ($user->tenant !== $tenantCode) {
            throw new UnauthorizedException('Tenant ' . $tenantCode . ' does not match user given, of tenant ' . $user->tenant, 403);
        }
        $spec = $this->resourceManager -> fetchTenantSpec($concept);
        if ($spec[0]['tenantcode'] !== $tenantCode) {
                throw new UnauthorizedException('The concept has tenant ' . 
                        $spec['tenantcode'] . 
                        ' which does not correspond to the request-s tenant  ' . $tenantCode,
                         403);
            
        }

        if (!($user->role === EDITOR || $user->role === ADMINISTRATOR || $user->role === ROOT)) {
            throw new UnauthorizedException('Your role ' . $user->role . ' does not give you permission to edit or delete concepts ', 403);
        }
        return true;
    }

    private function conceptDeleteAllowed(OpenSKOS_Db_Table_Row_User $user, $tenantCode, $concept) {
        $retVal = $this->conceptEditAllowed($user, $tenantCode, $concept);
        return $retVal;
    }

    private function conceptSchemeCreationAllowed(OpenSKOS_Db_Table_Row_User $user, $tenantCode, $resource) {
        return $this->resourceCreationAllowedBasic($user, $tenantCode, $resource);
    }

    private function conceptSchemeEditAllowed(OpenSKOS_Db_Table_Row_User $user, $tenantCode, $resource) {
        return $this->resourceEditAllowedBasic($user, $tenantCode, $resource);
    }

    private function conceptSchemeDeleteAllowed(OpenSKOS_Db_Table_Row_User $user, $tenantCode, $resource) {
        return $this->resourceDeleteAllowedBasic($user, $tenantCode, $resource);
    }

    private function setCreationAllowed(OpenSKOS_Db_Table_Row_User $user, $tenantCode, $resource) {
        return $this->resourceCreationAllowedBasic($user, $tenantCode, $resource);
    }

    private function setEditAllowed(OpenSKOS_Db_Table_Row_User $user, $tenantCode, $resource) {
        return $this->resourceEditAllowedBasic($user, $tenantCode, $resource);
    }

    private function setDeleteAllowed(OpenSKOS_Db_Table_Row_User $user, $tenantCode, $resource) {
        return $this->resourceDeleteAllowedBasic($user, $tenantCode, $resource);
    }

    private function tenantCreationAllowed(OpenSKOS_Db_Table_Row_User $user, $tenantCode, $resource) {
        return $this->resourceCreationAllowedBasic($user, $tenantCode, $resource);
    }

    private function tenantEditAllowed(OpenSKOS_Db_Table_Row_User $user, $tenantCode, $resource) {
        return $this->resourceEditAllowedBasic($user, $tenantCode, $resource);
    }

    private function tenantDeleteAllowed(OpenSKOS_Db_Table_Row_User $user, $tenantCode, $resource) {
        return $this->resourceDeleteAllowedBasic($user, $tenantCode, $resource);
    }

    private function skosCollectionCreationAllowed(OpenSKOS_Db_Table_Row_User $user, $tenantCode, $resource) {
        return $this->resourceCreationAllowedBasic($user, $tenantCode, $resource);
    }

    private function skosCollectionEditAllowed(OpenSKOS_Db_Table_Row_User $user, $tenantCode, $resource) {
        return $this->resourceEditAllowedBasic($user, $tenantCode, $resource);
    }

    private function skosCollectionDeleteAllowed(OpenSKOS_Db_Table_Row_User $user, $tenantCode, $resource) {
        return $this->resourceDeleteAllowedBasic($user, $tenantCode, $resource);
    }

    private function relationCreationAllowed(OpenSKOS_Db_Table_Row_User $user, $tenantCode, $resource) {
        return $this->resourceCreationAllowedBasic($user, $tenantCode, $resource);
    }

    private function relationEditAllowed(OpenSKOS_Db_Table_Row_User $user, $tenantCode, $resource) {
        return $this->resourceEditAllowedBasic($user, $tenantCode, $resource);
    }

    private function relationDeleteAllowed(OpenSKOS_Db_Table_Row_User $user, $tenantCode, $resource) {
        return $this->resourceDeleteAllowedBasic($user, $tenantCode, $resource);
    }

}
