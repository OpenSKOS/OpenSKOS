<?php
/**
 * Created by PhpStorm.
 * User: jsmit
 * Date: 27/08/2015
 * Time: 13:50
 */

namespace OpenSkos2\Rdf;


class Uri implements Object
{
    /**
     * @var string
     */
    protected $value;

    /**
     * Literal constructor.
     * @param string $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }


    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }



}