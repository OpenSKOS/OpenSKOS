<?php

namespace OpenSkos2\Validator\CommonProperties;

use OpenSkos2\Rdf\Resource as RdfResource;
use OpenSkos2\Rdf\ResourceManager as RdfResourceManager;

class SubresourceValidator 
{
   
    public static function validateSubresource(RdfResourceManager $manager, RdfResource $resource, $fieldname, $type, $obligatory)
    {
        $subresources = $resource->getProperty($fieldname);
        $l = count($subresources);
        $retVal=array();
        if ($obligatory) {
            if ($l < 1) {
                $retVal[] = 'No (valid) reference for subresource of type ' . $type . '  is given in the submitted resource.The resource must refer to at least one of them.';
                
            }
        }
        for ($i = 0; $i < $l; $i++) {
                if (!($manager->askForUri($subresources[$i], $type))) {
                    $retVal[] = 'The referred resource with uri ' . $subresources[$i] . ' does not exist.';
                    
                }
            }
        
        return $retVal;
    }
}