<?php

use OpenSkos2\Rdf\Resource as RdfResource;
use OpenSkos2\Rdf\ResourceManager as RdfResourceManager;

class SubresourceValidator
{
   
    public static function validateSubresource(RdfResourceManager $manager, RdfResource $resource, $fieldname, $type, $obligatory)
    {
        $subresources = $resource->getProperty($fieldname);
        $l = count($subresources);
        $retVal=true;
        if ($obligatory) {
            if ($l < 1) {
                $this->errorMessages[] = 'No (valid) reference for subresource of type ' . $type . '  is given in the submitted resource.The resource must refer to at least one of them.';
                $retVal = false;
            }
        }
        for ($i = 0; $i < $l; $i++) {
                if (!($manager->askForUri($subresources[$i], $type))) {
                    $this->errorMessages[] = 'The referred resource with uri ' . $subresources[$i] . ' does not exist.';
                    $retVal=false;
                }
            }
        
        return $retVal;
    }
}