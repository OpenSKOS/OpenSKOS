<?php

namespace OpenSkos2\MyInstitutionModules;
use OpenSkos2\Namespaces\Skos;

class Relations
{
    
    const FASTER = 'http://menzo.org/xmlns#faster';
    const SLOWER = 'http://menzo.org/xmlns#slower';
    const LONGER = 'http://menzo.org/xmlns#longer';
    
    public static $myrelations = array (
        'menzo:faster' => Relations::FASTER, 
        'menzo:slower' => Relations::SLOWER, 
        'menzo:longer' => Relations::LONGER
    );
    
    public static $inverses = array (
        Relations::FASTER => Relations::SLOWER,
        Relations::SLOWER => Relations::FASTER
    );
    
    // if direct relation belongs to the transitive closure
    // default is false
    public static $transitive = array (
        Relations::FASTER => true,
        Relations::SLOWER => true,
        Skos::BROADER => false,
        Skos::NARROWER => false
    );
}
