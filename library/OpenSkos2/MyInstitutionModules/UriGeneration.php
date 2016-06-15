<?php

namespace OpenSkos2\MyInstitutionModules;

use OpenSkos2\MyInstitutionModules\EPICHandleProxy;
use Rhumsaa\Uuid\Uuid;
use Zend_Controller_Action_Exception;
use OpenSkos2\Namespaces\Skos;

class UriGeneration {
    
   public static function generateUUID($parameters) {
        $uuidPlain=Uuid::uuid4();
        return $uuidPlain;
    }
    
    
    public static function generateURI($parameters) {
        $shorttype= self::getShortResourceType($parameters['type']);
        $plainUUID= $parameters['uuid'];    
        if (EPICHandleProxy::enabled() && EPIC_IS_ON) {
            // Create the PID
            $handleServerClient = EPICHandleProxy::getInstance();
            $forwardLocationPrefix = $handleServerClient->getForwardLocationPrefix();
            try {
                $handleServerGUIDPrefix = $handleServerClient->getGuidPrefix();
                $uuid = $handleServerGUIDPrefix  . $shorttype . "_" . $plainUUID;
                $handleServerClient->createNewHandleWithGUID($forwardLocationPrefix . $uuid, $uuid);
                $handleResolverUrl = $handleServerClient->getResolver();
		$handleServerPrefix = $handleServerClient->getPrefix();
                $uri = $handleResolverUrl . $handleServerPrefix . "/" . $uuid;
                return $uri;
            } catch (Exception $ex) {
                throw new Zend_Controller_Action_Exception('Failed to create a PID for the new Object: ' . $ex->getMessage(), 400);
            }
        } else {
            if (!isset($parameters['seturi'])) {
                $parameters['seturi']=null;
            }
            if (!isset($parameters['notation'])) {
                $parameters['notation']=null;
            }
            $uri = self::noEPIC($parameters['type'], $plainUUID, $parameters['seturi'], $parameters['notation']);
            return $uri;
        }
    }
    
    private static function getShortResourceType($rdfType) {
        $index = strrpos($rdfType, "#");
        $type = substr($rdfType, $index + 1);
        return strtolower($type);
    }

    private static function noEPIC($type, $plainUUID, $setUri, $notation) {
        $shortType = self::getShortResourceType($type);
        if ($type === Skos::CONCEPT || $type === Skos::CONCEPTSCHEME || $type === Skos::SKOSCOLLECTION) {
            if (isset($notation) && $notation !== null) {
                $uri = $setUri . '/' . $notation;
            } else {
                $uri = $setUri . '/' . $shortType . '_' . $plainUUID;
            }
        } else {
            $uri = URI_PREFIX . $shortType . '_' . $plainUUID;
        }
        return $uri;
    }
}