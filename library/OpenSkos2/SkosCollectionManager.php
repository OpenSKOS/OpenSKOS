<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace OpenSkos2;


use OpenSkos2\SkosCollection;
use OpenSkos2\Rdf\ResourceManager;

class SkosCollectionManager extends ResourceManager
{
    /**
     * What is the basic resource for this manager.
     * @var string NULL means any resource.
     */
    protected $resourceType = SkosCollection::TYPE;
    
     //check conditions when it can be deleted
    public function CanBeDeleted(){
        return true;
    }
    
}
