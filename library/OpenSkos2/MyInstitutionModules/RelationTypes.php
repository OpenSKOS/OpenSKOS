<?php

namespace OpenSkos2\MyInstitutionModules;

use OpenSkos2\Namespaces\Skos;

class RelationTypes
{

    const FASTER = 'http://menzo.org/xmlns#faster';
    const SLOWER = 'http://menzo.org/xmlns#slower';
    const LONGER = 'http://menzo.org/xmlns#longer';

    public static $myrelations = array(
        'menzo:faster' => RelationTypes::FASTER,
        'menzo:slower' => RelationTypes::SLOWER,
        'menzo:longer' => RelationTypes::LONGER
    );
    public static $inverses = array(
        RelationTypes::FASTER => RelationTypes::SLOWER,
        RelationTypes::SLOWER => RelationTypes::FASTER
    );
    // if direct relation belongs to the transitive closure
    // default is false
    public static $transitive = array(
        RelationTypes::FASTER => true,
        RelationTypes::SLOWER => true,
        Skos::BROADER => false,
        Skos::NARROWER => false
    );
}
