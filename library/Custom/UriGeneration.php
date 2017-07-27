<?php

namespace Custom;

use Custom\EPICHandleProxy;
use OpenSkos2\Exception\UriGenerationException;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Rdf\Literal;
use Rhumsaa\Uuid\Uuid;

class UriGeneration implements \OpenSkos2\Interfaces\UriGeneration
{

    private $manager;
    
    public function __construct($manager)
    {
        
        $this->manager = $manager;
    }
    public function generateUri($resource)
    {
        if (EPICHandleProxy::enabled()) {
            $uuid = Uuid::uuid4();
            $handleServerClient = EPICHandleProxy::getInstance();
            $forwardLocationPrefix = $handleServerClient->getForwardLocationPrefix();
            try {
                $handleServerGUIDPrefix = $handleServerClient->getGuidPrefix();
                $uuid = $handleServerGUIDPrefix . $uuid;
                $handleServerClient->createNewHandleWithGUID($forwardLocationPrefix . $uuid, $uuid);
                $handleResolverUrl = $handleServerClient->getResolver();
                $handleServerPrefix = $handleServerClient->getPrefix();
                $uri = $handleResolverUrl . $handleServerPrefix . "/" . $uuid;
            } catch (Exception $ex) {
                throw new Zend_Controller_Action_Exception(
                    'Failed to create a PID for the new Object: ' . $ex->getMessage(),
                    400
                );
            }
            if ($this->manager->askForUri($uri, true)) {
                throw new UriGenerationException(
                    'The generated uri "' . $uri . '" is already in use.'
                );
            }

            $resource->setUri($uri);
            $resource->setProperty(OpenSkos::UUID, new Literal($uuid));
            return $uri;
        } else {
            throw new UriGenerationException(
                'Epic is not enabled, change custom parameter'
            );
        }
    }
}
