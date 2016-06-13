<?php

namespace OpenSkos2\MyInstitutionModules;

use OpenSkos2\MyInstitutionModules\EPICHandleProxy;
use Rhumsaa\Uuid\Uuid;
use Zend_Controller_Action_Exception;

class UriGeneration {
    
   public static function generateUUID($tenantcode, $type) {
        $uuidPlain=Uuid::uuid4();
        return $uuidPlain;
    }
    
    
    public static function generateURI($plainUUID, $tenantcode, $type) {
        // tmp while EPIC service does not work
        $tmpRetVal = "http://tmp-bypass-epic/CCR_" .$type . "_" . $plainUUID;
        return $tmpRetVal;
        /// END TMP
        
        if (EPICHandleProxy::enabled()) {
            // Create the PID
            $handleServerClient = EPICHandleProxy::getInstance();
            $forwardLocationPrefix = $handleServerClient->getForwardLocationPrefix();
            try {
                $handleServerGUIDPrefix = $handleServerClient->getGuidPrefix();
                $uuid = $handleServerGUIDPrefix  . $type . "_" . $plainUUID;
                $handleServerClient->createNewHandleWithGUID($forwardLocationPrefix . $uuid, $uuid);
                $handleResolverUrl = $handleServerClient->getResolver();
		$handleServerPrefix = $handleServerClient->getPrefix();
                $uri = $handleResolverUrl . $handleServerPrefix . "/" . $uuid;
                return $uri;
            } catch (Exception $ex) {
                throw new Zend_Controller_Action_Exception('Failed to create a PID for the new Object: ' . $ex->getMessage(), 400);
            }
        } else {
            throw new Zend_Controller_Action_Exception('Failed to create a PID for the new Object because EPIC is not enabled', 400);
        }
    }
    
   
}