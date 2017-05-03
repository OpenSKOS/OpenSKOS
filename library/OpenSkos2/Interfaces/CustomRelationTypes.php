<?php

namespace OpenSkos2\Interfaces;

interface CustomRelationTypes
{

    public function getRelationTypes();

    public function getInverses();

    public function getTransitives();

    public function setRelationTypes($relationtypes);

    public function setInverses($inverses);

    public function setTransitives($transitives);
}
