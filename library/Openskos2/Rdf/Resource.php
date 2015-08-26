<?php
/**
 * Created by PhpStorm.
 * User: jsmit
 * Date: 26/08/2015
 * Time: 14:34
 */

namespace OpenSkos2\Rdf;


class Resource
{
    protected $properties = [];

    /**
     * @return array of Object[]
     */
    public function getProperties(){
        return $this->properties;
    }

    /**
     * @param $predicate
     * @return Object[]
     */
    public function getProperty($predicate) {
        return $this->properties[$predicate];
    }
}