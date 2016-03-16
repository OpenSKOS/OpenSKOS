<?php

namespace OpenSkos2\Validator\CommonProperties;

use OpenSkos2\Rdf\Resource as RdfResource;

class UniqueObligatoryProperty
{
    public static function validate(RdfResource $resource, $type, $isBoolean) {
        //var_dump($type);
        $retval = array();
        $val = $resource->getProperty($type);
        if (!count(array_filter($val))) {
            $retval[] = $type . ' is required for all resources.';
        }
        if (count(array_filter($val)) > 1) {
            $retval[] = 'There must be exactly 1 ' . $type . ' per resource. A few of them are given, or there are duplications.';
        }

       if ($isBoolean && count(array_filter($val)) > 0) {
            Checks::checkBoolean($val[0], $type, $retval);
        }
        //var_dump($retval);
        return $retval;
    }

}
