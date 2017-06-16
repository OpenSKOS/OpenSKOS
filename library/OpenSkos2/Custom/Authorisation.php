<?php

namespace OpenSkos2\Custom;

use OpenSKOS_Db_Table_Row_User;
use OpenSkos2\Concept;
use OpenSkos2\ConceptScheme;
use OpenSkos2\Set;
use OpenSkos2\Tenant;
use OpenSkos2\SkosCollection;
use OpenSkos2\RelationType;
use OpenSkos2\Roles;
use OpenSkos2\Namespaces\OpenSkos;


class Authorisation implements \OpenSkos2\Interfaces\Authorisation
{

    private $resourceManager;

    public function __construct($manager)
    {
        $this->resourceManager = $manager;
    }

    public function resourceCreateAllowed(OpenSKOS_Db_Table_Row_User $user, \OpenSkos2\Tenant $tenant, $set, $resource)
    {
        $type = $this->resourceManager->getResourceType();

        if ($type !== Tenant::TYPE && $type !== Set::TYPE) {
            $setIsValid = $this->checkSet($set, $resource);
            if (!$setIsValid) {
                throw new \Exception(
                    'The set code ' . $set->getCode()->getValue() .
                    ' from resource parameters does not match the set to which the resource refers'
                    . '(indirectly via schemes and collections if the resource is a concept)'
                );
            }
        }
        switch ($type) {
            case Concept::TYPE:
                $this->conceptCreateAllowed($user, $tenant, $resource);
                return;
            case ConceptScheme::TYPE:
                $this->conceptSchemeCreateAllowed($user, $tenant, $resource);
                return;
            case Set::TYPE:
                $this->setCreateAllowed($user, $tenant, $resource);
                return;
            case Tenant::TYPE:
                $this->tenantCreateAllowed($user);
                return;
            case SkosCollection::TYPE:
                $this->skosCollectionCreateAllowed($user, $tenant, $resource);
                return;
            case RelationType::TYPE:
                $this->relationCreateAllowed($user, $tenant, $resource);
                return;
            default:
                throw new \Exception('Unknown resource type is passed to the Authorisation checker');
        }
    }

    public function resourceEditAllowed(OpenSKOS_Db_Table_Row_User $user, \OpenSkos2\Tenant $tenant, $set, $resource)
    {
        $type = $this->resourceManager->getResourceType();

        if ($type !== Tenant::TYPE && $type !== Set::TYPE) {
            $setIsValid = $this->checkSet($set, $resource);
            if (!$setIsValid) {
                throw new \Exception(
                    'The set code ' . $set->getCode()->getValue() .
                    ' from resource parameters does not match the set to which the resource refers'
                    . '(indirectly via schemes and collections if the resource is concept)');
            }
        }
        switch ($type) {
            case Concept::TYPE:
                $this->conceptEditAllowed($user, $tenant, $resource);
                return;
            case ConceptScheme::TYPE:
                $this->conceptSchemeEditAllowed($user, $tenant, $resource);
                return;
            case Set::TYPE:
                $this->setEditAllowed($user, $tenant, $resource);
                return;
            case Tenant::TYPE:
                $this->tenantEditAllowed($user);
                return;
            case SkosCollection::TYPE:
                $this->skosCollectionEditAllowed($user, $tenant, $resource);
                return;
            case RelationType::TYPE:
                $this->relationEditAllowed($user, $tenant, $resource);
                return;
            default:
                throw new \Exception('Unknown resource type is passed to the Authorisation checker');
        }
    }

    public function resourceDeleteAllowed(OpenSKOS_Db_Table_Row_User $user, \OpenSkos2\Tenant $tenant, $set, $resource)
    {
        $type = $this->resourceManager->getResourceType();

        if ($type !== Tenant::TYPE && $type !== Set::TYPE) {
            $setIsValid = $this->checkSet($set, $resource);
            if (!$setIsValid) {
                throw new \Exception(
                    'The set code ' . $set->getCode()->getValue() .
                    ' from resource parameters does not match the set to which the resource refers'
                    . '(indirectly via schemes and collections if the resource is a concept)'
                );
            }
        }
        switch ($type) {
            case Concept::TYPE:
                $this->conceptDeleteAllowed($user, $tenant, $resource);
                return;
            case ConceptScheme::TYPE:
                $this->conceptSchemeDeleteAllowed($user, $tenant, $resource);
                return;
            case Set::TYPE:
                $this->setDeleteAllowed($user, $tenant, $resource);
                return;
            case Tenant::TYPE:
                $this->tenantDeleteAllowed($user);
                return;
            case SkosCollection::TYPE:
                $this->skosCollectionDeleteAllowed($user, $tenant, $resource);
                return;
            case RelationType::TYPE:
                $this->relationDeleteAllowed($user, $tenant, $resource);
                return;
            default:
                throw new \Exception('Unknown resource type is passed to the Authorisation checker');
        }
    }

    private function resourceDeleteAllowedBasic(
        OpenSKOS_Db_Table_Row_User $user,
        Tenant $tenant,
        $resource
    ) {
        $tenantCode = $tenant->getCode()->getValue();
        if ($user->tenant !== $tenantCode) {
            throw new \Exception(
                'Tenant ' . $tenantCode . ' does not match user given, of tenant ' .
                $user->tenant
            );
        }
        if (!($user->role === Roles::ADMINISTRATOR ||
            $user->role === Roles::ROOT || $user->role === Roles::EDITOR)){
            throw new \Exception('User the the role '.$user->role. ' cannot delete this resource');
        }
    }

    private function resourceCreateAllowedBasic(
        OpenSKOS_Db_Table_Row_User $user,
        Tenant $tenant,
        $resource
    ) {
        $tenantCode = $tenant->getCode()->getValue();
        if ($user->tenant !== $tenantCode) {
            throw new \Exception(
                'Tenant ' . $tenantCode .
                ' does not match user given, of tenant ' . $user->tenant
            );
        }
        if (!($user->role === Roles::ADMINISTRATOR ||
            $user->role === Roles::ROOT || $user->role === Roles::EDITOR)){
            throw new \Exception('User the the role '.$user->role. ' cannot delete this resource');
        }
    }

    private function resourceEditAllowedBasic(
        OpenSKOS_Db_Table_Row_User $user,
        Tenant $tenant,
        $resource
    ) {
        $tenantCode = $tenant->getCode()->getValue();
        if ($user->tenant !== $tenantCode) {
            throw new \Exception(
                'Tenant ' . $tenantCode . ' does not match user given, of tenant ' .
                $user->tenant
            );
        }
        if (!($user->role === Roles::ADMINISTRATOR ||
            $user->role === Roles::ROOT || $user->role === Roles::EDITOR)){
            throw new \Exception('User the the role '.$user->role. ' cannot delete this resource');
        }
    }

    private function conceptCreateAllowed(
        OpenSKOS_Db_Table_Row_User $user,
        Tenant $tenant,
        $conceptToPost
    ) {
        $tenantCode = $tenant->getCode()->getValue();
        // the group of users which can post to certain sets,
        // skos-collections or upon certain schemata, can be limited
        if ($user->tenant !== $tenantCode) {
            throw new \Exception(
                'Tenant ' . $tenantCode . ' does not match user given, of tenant ' .
                $user->tenant
            );
        }
        if (!($user->role === Roles::EDITOR ||
            $user->role === Roles::ADMINISTRATOR ||
            $user->role === Roles::ROOT)) {
            throw new \Exception(
                'Your role ' . $user->role . ' does not give you permission to create concepts '
            );
        }
        
    }

    private function conceptEditAllowed(OpenSKOS_Db_Table_Row_User $user, \OpenSkos2\Tenant $tenant, $concept)
    {
        $tenantCode = $tenant->getCode()->getValue();
        if ($user->tenant !== $tenantCode) {
            throw new \Exception(
                'Tenant ' . $tenantCode . ' does not match user given, of tenant ' .
                $user->tenant
            );
        }
        $spec = $this->resourceManager->fetchConceptSpec($concept);
        if ($spec[0]['tenantcode'] !== $tenantCode) {
            throw new \Exception('The concept has tenant ' .
            $spec['tenantcode'] .
            ' which does not correspond to the request-s tenant  ' . $tenantCode);
        }

        if (!($user->role === Roles::ADMINISTRATOR ||
            $user->role === Roles:: ROOT ||
            $user->role === Roles:: EDITOR)) {
            if ($user->uri !== $concept->getCreator()) {
                throw new \Exception(
                    'Your role ' . $user->role .
                    ' does not give you permission to edit or delete a concept whoich you do not ow.'
                );
            }
        }
        return true;
    }

    private function conceptDeleteAllowed(OpenSKOS_Db_Table_Row_User $user, \OpenSkos2\Tenant $tenant, $concept)
    {
        $this->conceptEditAllowed($user, $tenant, $concept);
    }

    private function conceptSchemeCreateAllowed(OpenSKOS_Db_Table_Row_User $user, \OpenSkos2\Tenant $tenant, $resource)
    {
        $this->resourceCreateAllowedBasic($user, $tenant, $resource);
    }

    private function conceptSchemeEditAllowed(OpenSKOS_Db_Table_Row_User $user, \OpenSkos2\Tenant $tenant, $resource)
    {
        $this->resourceEditAllowedBasic($user, $tenant, $resource);
    }

    private function conceptSchemeDeleteAllowed(OpenSKOS_Db_Table_Row_User $user, \OpenSkos2\Tenant $tenant, $resource)
    {
        $this->resourceDeleteAllowedBasic($user, $tenant, $resource);
    }

    private function setCreateAllowed(OpenSKOS_Db_Table_Row_User $user, \OpenSkos2\Tenant $tenant, $resource)
    {
        $this->resourceCreateAllowedBasic($user, $tenant, $resource);
    }

    private function setEditAllowed(OpenSKOS_Db_Table_Row_User $user, \OpenSkos2\Tenant $tenant, $resource)
    {
        $this->resourceEditAllowedBasic($user, $tenant, $resource);
    }

    private function setDeleteAllowed(OpenSKOS_Db_Table_Row_User $user, \OpenSkos2\Tenant $tenant, $resource)
    {
        return $this->resourceDeleteAllowedBasic($user, $tenant, $resource);
    }

    private function tenantCreateAllowed(OpenSKOS_Db_Table_Row_User $user)
    {
        if (!($user->role === Roles::ADMINISTRATOR ||
            $user->role === Roles::ROOT || $user->role === Roles::EDITOR)){
            throw new \Exception('User the the role '.$user->role. ' cannot create this resource');
        }
    }

    private function tenantEditAllowed(OpenSKOS_Db_Table_Row_User $user)
    {
        if (!($user->role === Roles::ADMINISTRATOR ||
            $user->role === Roles::ROOT || $user->role === Roles::EDITOR)){
            throw new \Exception('User the the role '.$user->role. ' cannot edit this resource');
        }
    }

    private function tenantDeleteAllowed(OpenSKOS_Db_Table_Row_User $user)
    {
        if (!($user->role === Roles::ADMINISTRATOR ||
            $user->role === Roles::ROOT || $user->role === Roles::EDITOR)){
            throw new \Exception('User the the role '.$user->role. ' cannot delete this resource');
        }
    }

    private function skosCollectionCreateAllowed(OpenSKOS_Db_Table_Row_User $user, \OpenSkos2\Tenant $tenant, $resource)
    {
        $this->resourceCreateAllowedBasic($user, $tenant, $resource);
    }

    private function skosCollectionEditAllowed(OpenSKOS_Db_Table_Row_User $user, \OpenSkos2\Tenant $tenant, $resource)
    {
        $this->resourceEditAllowedBasic($user, $tenant, $resource);
    }

    private function skosCollectionDeleteAllowed(OpenSKOS_Db_Table_Row_User $user, \OpenSkos2\Tenant $tenant, $resource)
    {
        $this->resourceDeleteAllowedBasic($user, $tenant, $resource);
    }

    private function relationCreateAllowed(OpenSKOS_Db_Table_Row_User $user, \OpenSkos2\Tenant $tenant, $resource)
    {
        $this->resourceCreateAllowedBasic($user, $tenant, $resource);
    }

    private function relationEditAllowed(OpenSKOS_Db_Table_Row_User $user, \OpenSkos2\Tenant $tenant, $resource)
    {
       $this->resourceEditAllowedBasic($user, $tenant, $resource);
    }

    private function relationDeleteAllowed(OpenSKOS_Db_Table_Row_User $user, \OpenSkos2\Tenant $tenant, $resource)
    {
        $this->resourceDeleteAllowedBasic($user, $tenant, $resource);
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
                $retval = true;
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
                throw new \Exception(
                    'The concept under submission via its schemes and skos:collections belongs '
                    . 'to at least two sets,  ' . $setUri . ', ' . $spec[$i]['seturi'].
                    ". The concept cannot be submitted. "
                );
            }
        }
        return $setUri;
    }
}
