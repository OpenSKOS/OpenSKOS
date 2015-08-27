<?php
/**
 * Created by PhpStorm.
 * User: jsmit
 * Date: 27/08/2015
 * Time: 13:50
 */

namespace OpenSkos2\Rdf;


class Literal implements Object
{
    /**
     * @var string
     */
    protected $language;

    /**
     * @var string
     */
    protected $value;

    /**
     * Literal constructor.
     * @param string $value
     * @param string $language
     */
    public function __construct($value, $language = null)
    {
        $this->value = $value;
        $this->language = $language;
    }

    /**
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }



}