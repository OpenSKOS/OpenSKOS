<?php

namespace OpenSkos2\Validator\CommonProperties;

use OpenSkos2\Rdf\Resource as RdfResource;
use OpenSkos2\Validator\CommonProperties\Checks;

class UniqueOptionalProperty
{
    public static function validate(RdfResource $resource, $type, $isBoolean) {
        $val = $resource->getProperty($type);
        $retval = array();
        if (count(array_filter($val)) > 1) {
            $retval[] = 'There must be not more than 1 ' . $type . ' per resource. You have submitted a few of them';
        }

        if ($isBoolean && count(array_filter($val)) > 0) {
            Checks::checkBoolean($val[0], $type, $retval);
        }

        return $retval;
    }

}
