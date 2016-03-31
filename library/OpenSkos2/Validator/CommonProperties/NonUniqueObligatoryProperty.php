<?php

namespace OpenSkos2\Validator\CommonProperties;

use OpenSkos2\Rdf\Resource as RdfResource;

class NonUniqueObligatoryProperty
{
    public static function validate(RdfResource $resource, $type, $isBoolean) {
        $retval = array();
        $values = $resource->getProperty($type);
        if (!count(array_filter($values))) {
            $retval[] = $type . ' is required for all resources of this type';
        }
       
       
       if ($isBoolean) {
            foreach ($values as $val) {
                Checks::checkBoolean($val, $type, $retval);
            }
        }
       
        return $retval;
    }

}
