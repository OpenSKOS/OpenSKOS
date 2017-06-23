<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace OpenSkos2;

use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Namespaces\Rdf;
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Rdf\Resource;
use OpenSkos2\Rdf\Uri;

class SkosCollection extends Resource
{

    const TYPE = Skos::SKOSCOLLECTION;

    public function __construct($uri = null)
    {
        parent::__construct($uri);
        $this->addProperty(Rdf::TYPE, new Uri(self::TYPE));
    }

    public function getDescription()
    {
        if ($this->hasProperty(DcTerms::DESCRIPTION)) {
            return (string) $this->getPropertySingleValue(DcTerms::DESCRIPTION);
        } else {
            return null;
        }
    }

  
}
