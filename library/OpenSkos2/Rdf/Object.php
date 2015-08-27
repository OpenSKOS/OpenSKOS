<?php
/**
 * Created by PhpStorm.
 * User: jsmit
 * Date: 26/08/2015
 * Time: 14:55
 */

namespace OpenSkos2\Rdf;


class Object
{
    const TYPE_URI = 'uri';
    const TYPE_LITERAL = 'literal';


    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $value;

    /**
     * Object constructor.
     * @param $type
     * @param $value
     */
    public function __construct($type, $value)
    {
        $this->type = $type;
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }


}