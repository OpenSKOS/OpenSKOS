<?php
/**
 * Created by PhpStorm.
 * User: jsmit
 * Date: 26/08/2015
 * Time: 16:58
 */

namespace OpenSkos2\Validator;


use OpenSkos2\Rdf\Resource;

abstract class ResourceValidator
{
    protected $errorMessage;

    abstract public function validate(Resource $resource);

    /**
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

}