<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace OpenSkos2;

use OpenSkos2\Rdf\ResourceManager;

class SkosCollectionManager extends ResourceManager
{
    /**
     * What is the basic resource for this manager.
     * @var string NULL means any resource.
     */
    protected $resourceType = SkosCollection::TYPE;
    
    public function fetchConceptsForSkosCollection($uri) {
        $sparqlQuery = 'select ?s  where {?s <http://openskos.org/xmlns#inSkosCollection>  <' . $uri . '> . }';
        //\Tools\Logging::var_error_log(" Query \n", $sparqlQuery, '/app/data/Logger.txt');
        $resource = $this->query($sparqlQuery);
        return $resource;
    }
}
