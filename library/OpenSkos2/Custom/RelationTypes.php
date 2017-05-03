<?php

namespace OpenSkos2\Custom;

use OpenSkos2\Namespaces\Skos;

class RelationTypes implements OpenSkos2\Interfaces\CustomRelationTypes
{

    const FASTER = 'http://menzo.org/xmlns#faster';
    const SLOWER = 'http://menzo.org/xmlns#slower';
    const LONGER = 'http://menzo.org/xmlns#longer';

    protected $relationtypes = array();
    protected $inverses = array();
    protected $transitives = array();

    public function _construct()
    {
        $this->relationtypes = array(
            'menzo:faster' => RelationTypes::FASTER,
            'menzo:slower' => RelationTypes::SLOWER,
            'menzo:longer' => RelationTypes::LONGER
        );


        $this->inverses = array(
            RelationTypes::FASTER => RelationTypes::SLOWER,
            RelationTypes::SLOWER => RelationTypes::FASTER
        );

        $this->transitives = array(
            RelationTypes::FASTER => true,
            RelationTypes::SLOWER => true,
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
