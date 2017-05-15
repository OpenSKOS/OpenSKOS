<?php

namespace OpenSkos2\Custom;

use OpenSKOS_Db_Table_Row_User;
use OpenSkos2\Concept;
use OpenSkos2\ConceptScheme;
use OpenSkos2\Set;
use OpenSkos2\Tenant;
use OpenSkos2\SkosCollection;
use OpenSkos2\RelationType;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Api\Exception\UnauthorizedException;

class Authorisation implements \OpenSkos2\Interfaces\Authorisation
{

    private $resourceManager;

    public function __construct($manager)
    {
        $this->resourceManager = $manager;
    }

    public function resourceCreationAllowed(OpenSKOS_Db_Table_Row_User $user, $tenant, $set, $resource)
    {
        $type = $this->resourceManager->getResourceType();

        if ($type !== Tenant::TYPE && $type !== Set::TYPE) {
            $setIsValid = $this->checkSet($set, $resource);
            if (!$setIsValid) {
                throw new UnauthorizedException('The set code ' . $set->getCode()->getValue() . ' from resource parameters does not match the set to which the resource refers (indirectly via schemes and collections if the resource is a concept)', 403);
            }
        }
        switch ($type) {
            case Concept::TYPE:
                return $this->conceptCreationAllowed($user, $tenant, $resource);
            case ConceptScheme::TYPE:
                return $this->conceptSchemeCreationAllowed($user, $tenant, $resource);
            case Set::TYPE:
                return $this->setCreationAllowed($user, $tenant, $resource);
            case Tenant::TYPE:
                return $this->tenantCreationAllowed($user);
            case SkosCollection::TYPE:
                return $this->skosCollectionCreationAllowed($user, $tenant, $resource);
            case RelationType::TYPE:
                return $this->relationCreationAllowed($user, $tenant, $resource);
            default:
                return false;
        }
    }

    public function resourceEditAllowed(OpenSKOS_Db_Table_Row_User $user, $tenant, $set, $resource)
    {
        $type = $this->resourceManager->getResourceType();

        if ($type !== Tenant::TYPE && $type !== Set::TYPE) {
            $setIsValid = $this->checkSet($set, $resource);
            if (!$setIsValid) {
                throw new UnauthorizedException('The set code ' . $set->getCode()->getValue() . ' from resource parameters does not match the set to which the resource refers (indirectly via schemes and collections if the resource is concept)', 403);
            }
        }
        switch ($type) {
            case Concept::TYPE:
                return $this->conceptEditAllowed($user, $tenant, $resource);
            case ConceptScheme::TYPE:
                return $this->conceptSchemeEditAllowed($user, $tenant, $resource);
            case Set::TYPE:
                return $this->setEditAllowed($user, $tenant, $resource);
            case Tenant::TYPE:
                return $this->tenantEditAllowed($user);
            case SkosCollection::TYPE:
                return $this->skosCollectionEditAllowed($user, $tenant, $resource);
            case RelationType::TYPE:
                return $this->relationEditAllowed($user, $tenant, $resource);
            default:
                return false;
        }
    }

    public function resourceDeleteAllowed(OpenSKOS_Db_Table_Row_User $user, $tenant, $set, $resource)
    {
        $type = $this->resourceManager->getResourceType();

        if ($type !== Tenant::TYPE && $type !== Set::TYPE) {
            $setIsValid = $this->checkSet($set, $resource);
            if (!$setIsValid) {
                throw new UnauthorizedException('The set code ' . $set->getCode()->getValue() . ' from resource parameters does not match the set to which the resource refers (indirectly via schemes and collections if the resource is a concept)', 403);
            }
        }
        switch ($type) {
            case Concept::TYPE:
                return $this->conceptDeleteAllowed($user, $tenant, $resource);
            case ConceptScheme::TYPE:
                return $this->conceptSchemeDeleteAllowed($user, $tenant, $resource);
            case Set::TYPE:
                return $this->setDeleteAllowed($user, $tenant, $resource);
            case Tenant::TYPE:
                return $this->tenantDeleteAllowed($user);
            case SkosCollection::TYPE:
                return $this->skosCollectionDeleteAllowed($user, $tenant, $resource);
            case RelationType::TYPE:
                return $this->relationDeleteAllowed($user, $tenant, $resource);
            default:
                return false;
        }
    }

    private function resourceDeleteAllowedBasic(OpenSKOS_Db_Table_Row_User $user, Tenant $tenant, $resource)
    {
        $tenantCode = $tenant->getCode()->getValue();
        if ($user->tenant !== $tenantCode) {
            throw new UnauthorizedException('Tenant ' . $tenantCode . ' does not match user given, of tenant ' . $user->tenant, 403);
        }
        return ($user->role === ADMINISTRATOR || $user->role === ROOT || $user->role === EDITOR);
    }

    private function resourceCreationAllowedBasic(OpenSKOS_Db_Table_Row_User $user, Tenant $tenant, $resource)
    {
        $tenantCode = $tenant->getCode()->getValue();
        if ($user->tenant !== $tenantCode) {
            throw new UnauthorizedException('Tenant ' . $tenantCode . ' does not match user given, of tenant ' . $user->tenant, 403);
        }
        return ($user->role === ADMINISTRATOR || $user->role === ROOT || $user->role === EDITOR);
    }

    private function resourceEditAllowedBasic(OpenSKOS_Db_Table_Row_User $user, Tenant $tenant, $resource)
    {
        $tenantCode = $tenant->getCode()->getValue();
        if ($user->tenant !== $tenantCode) {
            throw new UnauthorizedException('Tenant ' . $tenantCode . ' does not match user given, of tenant ' . $user->tenant, 403);
        }
        return ($user->role === ADMINISTRATOR || $user->role === ROOT || $user->role === EDITOR);
    }

    private function conceptCreationAllowed(OpenSKOS_Db_Table_Row_User $user, Tenant $tenant, $conceptToPost)
    {
        $tenantCode = $tenant->getCode()->getValue();
        // the group of users which can post to certain sets, skos-collections or upon certain schemata, can be limited
        if ($user->tenant !== $tenantCode) {
            throw new UnauthorizedException('Tenant ' . $tenantCode . ' does not match user given, of tenant ' . $user->tenant, 403);
        }
        if (!($user->role === EDITOR || $user->role === ADMINISTRATOR || $user->role === ROOT)) {
            throw new UnauthorizedException('Your role ' . $user->role . ' does not give you permission to create concepts ', 403);
        }
        return true;
    }

    private function conceptEditAllowed(OpenSKOS_Db_Table_Row_User $user, Tenant $tenant, $concept)
    {
        $tenantCode = $tenant->getCode()->getValue();
        if ($user->tenant !== $tenantCode) {
            throw new UnauthorizedException('Tenant ' . $tenantCode . ' does not match user given, of tenant ' . $user->tenant, 403);
        }
        $spec = $this->resourceManager->fetchTenantSpec($concept);
        if ($spec[0]['tenantcode'] !== $tenantCode) {
            throw new UnauthorizedException('The concept has tenant ' .
            $spec['tenantcode'] .
            ' which does not correspond to the request-s tenant  ' . $tenantCode, 403);
        }

        if (!($user->role === ADMINISTRATOR || $user->role === ROOT)) {
            if ($user->uri !== $concept->getCreator()) {
                throw new UnauthorizedException('Your role ' . $user->role . ' does not give you permission to edit or delete a concept whoich you do not ow.', 403);
            }
        }
        return true;
    }

    private function conceptDeleteAllowed(OpenSKOS_Db_Table_Row_User $user, Tenant $tenant, $concept)
    {
        return $this->conceptEditAllowed($user, $tenant, $concept);
    }

    private function conceptSchemeCreationAllowed(OpenSKOS_Db_Table_Row_User $user, Tenant $tenant, $resource)
    {
        return $this->resourceCreationAllowedBasic($user, $tenant, $resource);
    }

    private function conceptSchemeEditAllowed(OpenSKOS_Db_Table_Row_User $user, Tenant $tenant, $resource)
    {
        return $this->resourceEditAllowedBasic($user, $tenant, $resource);
    }

    private function conceptSchemeDeleteAllowed(OpenSKOS_Db_Table_Row_User $user, Tenant $tenant, $resource)
    {
        return $this->resourceDeleteAllowedBasic($user, $tenant, $resource);
    }

    private function setCreationAllowed(OpenSKOS_Db_Table_Row_User $user, Tenant $tenant, $resource)
    {
        return $this->resourceCreationAllowedBasic($user, $tenant, $resource);
    }

    private function setEditAllowed(OpenSKOS_Db_Table_Row_User $user, Tenant $tenant, $resource)
    {
        return $this->resourceEditAllowedBasic($user, $tenant, $resource);
    }

    private function setDeleteAllowed(OpenSKOS_Db_Table_Row_User $user, Tenant $tenant, $resource)
    {
        return $this->resourceDeleteAllowedBasic($user, $tenant, $resource);
    }

    private function tenantCreationAllowed(OpenSKOS_Db_Table_Row_User $user)
    {
        return ($user->role === ADMINISTRATOR || $user->role === ROOT);
    }

    private function tenantEditAllowed(OpenSKOS_Db_Table_Row_User $user)
    {
        return ($user->role === ADMINISTRATOR || $user->role === ROOT);
    }

    private function tenantDeleteAllowed(OpenSKOS_Db_Table_Row_User $user)
    {
        return ($user->role === ADMINISTRATOR || $user->role === ROOT);
    }

    private function skosCollectionCreationAllowed(OpenSKOS_Db_Table_Row_User $user, Tenant $tenant, $resource)
    {
        return $this->resourceCreationAllowedBasic($user, $tenant, $resource);
    }

    private function skosCollectionEditAllowed(OpenSKOS_Db_Table_Row_User $user, Tenant $tenant, $resource)
    {
        return $this->resourceEditAllowedBasic($user, $tenant, $resource);
    }

    private function skosCollectionDeleteAllowed(OpenSKOS_Db_Table_Row_User $user, Tenant $tenant, $resource)
    {
        return $this->resourceDeleteAllowedBasic($user, $tenant, $resource);
    }

    private function relationCreationAllowed(OpenSKOS_Db_Table_Row_User $user, Tenant $tenant, $resource)
    {
        return $this->resourceCreationAllowedBasic($user, $tenant, $resource);
    }

    private function relationEditAllowed(OpenSKOS_Db_Table_Row_User $user, Tenant $tenant, $resource)
    {
        return $this->resourceEditAllowedBasic($user, $tenant, $resource);
    }

    private function relationDeleteAllowed(OpenSKOS_Db_Table_Row_User $user, Tenant $tenant, $resource)
    {
        return $this->resourceDeleteAllowedBasic($user, $tenant, $resource);
    }

    private function checkSet($set, $resource)
    {
        $setUri = $set->getUri();
        switch ($resource->getType()->getUri()) {
            case ConceptScheme::TYPE:
                $sets = $resource->getProperty(OpenSkos::SET);
                $retval = ($setUri === ($sets[0]->getUri()) );
                break;
            case SkosCollection::TYPE:
                $sets = $resource->getProperty(OpenSkos::SET);
                $retval = ($setUri === ($sets[0]->getUri()) );
                break;
            case Concept::TYPE:
                $setUriRef = $this->checkUniqueSet($resource);
                $retval = ($setUri === $setUriRef);
                break;
            default:
                $retval = TRUE;
                break;
        }
        return $retval;
    }

    private function checkUniqueSet($concept)
    {
        $spec = $this->resourceManager->fetchTenantSpecForConceptToAdd($concept);
        $setUri = $spec[0]['seturi'];
        for ($i = 1; $i < count($spec); $i++) {
            if ($setUri !== $spec[$i]['seturi']) {
                throw new UnauthorizedException('The concept under submission via its schemes and skos:collections belongs to at least two sets,  ' . $setUri . ', ' . $spec[$i]['seturi']. ". The concept cannot be submitted. ", 500);
            }
        }
        return $setUri;
    }

}
