<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace OpenSkos2;

use OpenSkos2\Rdf\ResourceManager;
use OpenSkos2\Rdf\Resource;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Api\Exception\ApiException;

require_once dirname(__FILE__) .'/config.inc.php';

class Preprocessor {
    
    private $manager;
    private $resourceType;
    private $userUri;
    
    function __construct(ResourceManager $resManager, $resourceType, $userUri) {
        $this->manager = $resManager;
        $this->resourceType = $resourceType;
        $this->userUri = $userUri;
    }

    public function forCreation(Resource $resourceObject, $params, $autoGenerateUuidUriWhenAbsent) {
        
        $preprocessed = $resourceObject;
        $preprocessed->addMetadata($this->userUri, $params, null);
        
        if ($this->resourceType === Skos::CONCEPTSCHEME || $this->resourceType === Skos::SKOSCOLLECTION) {
            $sets = $preprocessed->getProperty(OpenSkos::SET);
            if (count($sets) < 1) {
                throw new ApiException('The set (former known as tenant collection) of the resource is not given', 400);
            } else {
                $params['seturi']=$sets[0]->getUri();
            }
            $preprocessed->unsetProperty(OpenSkos::TENANT);
        }
        
        if ($this->resourceType === Skos::CONCEPT) {
            $preprocessed->unsetProperty(OpenSkos::SET);
            $preprocessed->unsetProperty(OpenSkos::TENANT);
        }

        if ($autoGenerateUuidUriWhenAbsent) {
            $params['type'] = $this->resourceType;     
            if ($this->resourceType === Skos::CONCEPT) {
                    $notations = $preprocessed->getProperty(Skos::NOTATION);
                    if (count($notations) === 0) {
                        $params['notation'] = null;
                    } else {
                        $params['notation'] = $notations[0];
                    }
            }
            $preprocessed->selfGenerateUuidAndUriWhenAbsent($this->manager, $params);
        };
        return $preprocessed;
    }
    
    
    

    public function forUpdate(Resource $resourceObject, $params) {
        
        $uri = $resourceObject->getUri();
        $existingResource = $this->manager->fetchByUri($uri, $this->resourceType);
        if ($this->manager->getResourceType() !== Relation::TYPE) { // we do not have an uuid for relations
            if ($resourceObject->getUuid() !== null) {
                $uuidNew = $resourceObject->getUuid()->getValue();
            } else {
                $uuidNew = null;
            }
            $uuidOld = $existingResource->getUuid()->getValue();
            if ($uuidNew !== null && $uuidNew !== $uuidOld) {
                throw new ApiException('You cannot change UUID of the resouce. Keep it ' . $uuidOld, 400);
            }
        }
        $preprocessed = $resourceObject;
        if ($this->resourceType === Skos::CONCEPT) {
            $preprocessed->unsetProperty(OpenSkos::SET);
            $preprocessed->unsetProperty(OpenSkos::TENANT);
        }
        if ($this->resourceType === Skos::CONCEPTSCHEME || $this->resourceType === Skos::SKOSCOLLECTION) {
            $preprocessed->unsetProperty(OpenSkos::TENANT);
        }
        $preprocessed->addMetadata($this->userUri, $params, $existingResource);
        return $preprocessed;
    }

}
