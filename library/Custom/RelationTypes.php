<?php

namespace Custom;

use OpenSkos2\Namespaces\Skos;

class RelationTypes implements \OpenSkos2\Interfaces\CustomRelationTypes
{

    const FASTER = 'http://menzo.org/xmlns#faster';
    const SLOWER = 'http://menzo.org/xmlns#slower';
    const LONGER = 'http://menzo.org/xmlns#longer';
    const WARMER = 'http://menzo.org/xmlns#warmer';

    protected $relationtypes = array();
    protected $inverses = array();
    protected $transitives = array();

    public function __construct()
    {
        $this->relationtypes = array(
            'menzo:faster' => RelationTypes::FASTER,
            'menzo:slower' => RelationTypes::SLOWER,
            'menzo:longer' => RelationTypes::LONGER,
            'menzo:warmer' => RelationTypes::WARMER
        );


        $this->inverses = array(
            RelationTypes::FASTER => RelationTypes::SLOWER,
            RelationTypes::SLOWER => RelationTypes::FASTER
        );

        $this->transitives = array(
            RelationTypes::FASTER => true,
            RelationTypes::SLOWER => true,
            RelationTypes::WARMER => true,
            Skos::BROADER => false,
            Skos::NARROWER => false
        );
    }

    public function getRelationTypes()
    {
        return $this->relationtypes;
    }

    public function getInverses()
    {
        return $this->inverses;
    }

    public function getTransitives()
    {
        return $this->transitives;
    }

    public function setRelationTypes($relationtypes)
    {
        $this->relationtypes = $relationtypes;
    }

    public function setInverses($inverses)
    {
        $this->relationtypes = $inverses;
    }

    public function setTransitives($transitives)
    {
        $this->transitives = $transitives;
    }
}
