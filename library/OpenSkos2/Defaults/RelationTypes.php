<?php

namespace OpenSkos2\Defaults;

class RelationTypes implements \OpenSkos2\Interfaces\CustomRelationTypes
{

    
    protected $relationtypes = array();
    protected $inverses = array();
    protected $transitives = array();

   
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
