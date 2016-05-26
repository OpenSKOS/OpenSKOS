<?php

namespace OpenSkos2\MyInstitutionModules;
use OpenSkos2\Namespaces\Skos;

class Relations
{
    
    const FASTER = 'http://menzo.org/xmlns#faster';
    const SLOWER = 'http://menzo.org/xmlns#slower';
    const STRONGER = 'http://menzo.org/xmlns#stronger';
    const WEAKER = 'http://menzo.org/xmlns#weaker';
    
    public static $myrelations = array (
        'menzo:faster' => Relations::FASTER, 
        'menzo:slower' => Relations::SLOWER, 
        'menzo:stronger' => Relations::STRONGER, 
        'menzo:weaker' => Relations::WEAKER
    );
    
    public static $inverses = array (
        Relations::FASTER => Relations::SLOWER,
        Relations::SLOWER => Relations::FASTER,
        Relations::STRONGER => Relations::WEAKER,
        Relations::WEAKER => Relations::STRONGER
    );
    
    // if direct relation belongs to the transitive closure
    // default is false
    public static $transitive = array (
        Relations::FASTER => true,
        Relations::SLOWER => true,
        Relations::STRONGER => true,
        Relations::WEAKER => true,
        Skos::BROADER => false,
        Skos::NARROWER => false
    );
}
