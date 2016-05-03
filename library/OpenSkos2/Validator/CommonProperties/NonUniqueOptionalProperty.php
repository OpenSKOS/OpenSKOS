<?php

namespace OpenSkos2\Validator\CommonProperties;

use OpenSkos2\Rdf\Resource as RdfResource;
use OpenSkos2\Validator\CommonProperties\Checks;

class NonUniqueOptionalProperty {

    public static function validate(RdfResource $resource, $type, $isBoolean) {
        $vals = $resource->getProperty($type);
        $retval = array();
        if ($isBoolean) {
        foreach ($vals as $val) {
            Checks::checkBoolean($val, $type, $retval);
            }
        }
        return $retval;
    }
}
