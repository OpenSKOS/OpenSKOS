<?php

/**
 * OpenSKOS
 *
 * LICENSE
 *
 * This source file is subject to the GPLv3 license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @category   OpenSKOS
 * @package    OpenSKOS
 * @copyright  Copyright (c) 2015 Picturae (http://www.picturae.com)
 * @author     Picturae
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 */
namespace OpenSkos2;

use OpenSkos2\Rdf\ResourceManager;
use OpenSkos2\Concept;
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Rdf\Uri;

class RelationManager extends ResourceManager
{
    /**
     * What is the basic resource for this manager.
     * @var string NULL means any resource.
     */
    protected $resourceType = Relation::TYPE;
    
    public function fetchUriName() {
         $uris = Skos::getSkosRelationsTypes();
         $skosrels = [];
         foreach ($uris as $uri) {
              $border = strrpos($uri, "#");
              $name = substr($uri, $border+1);
              $skosrels[$name] = $uri;
         }
         $userrels = parent::fetchUriName();
         $result = array_merge($skosrels, $userrels);
         return $result;
    }
    
    public static function fetchUris(){
         $skosrels = Skos::getSkosRelationsTypes();
         $userrels = array_values(parent::fetchUriName());
         $result = array_merge($skosrels, $userrels);
         return $result;
    }
    
    public function fetchAllRelationsOfType($relationType, $sourceSchemata, $targetSchemata) {
        $rels = [];
        $sSchemata = [];
        $tSchemata = [];
        if (isset($relationType)) {
        $rels = explode(",", $relationType);
        }
        if (isset($sourceSchemata)) {
        $sSchemata = explode(",", $sourceSchemata);
        }
        if (isset($targetSchemata)) {
        $tSchemata = explode(",", $targetSchemata);
        }
        $relFilterStr="";
        $existingRelations = array_merge(Skos::getSkosRelationsTypes(), $this->getUserRelationQNameUris());
        
        if (count($rels) > 0) {
            if (in_array($rels[0], $existingRelations)) {
                $relFilterStr = '( ?rel = <' . $rels[0] . '>';
            } else {
                throw new \OpenSkos2\Api\Exception\ApiException('Relation ' . $rels[0] . " is not implemented.", 501);
            }
            
            for ($i = 1; $i < count($rels); $i++) {
                if (in_array($rels[$i], $existingRelations)) {
                    $relFilterStr = $relFilterStr . ' || ?rel = <' . $rels[$i] . '>';
                } else {
                    throw new \OpenSkos2\Api\Exception\ApiException('Relation ' . $rels[$i] . " is not implemented.", 501);
                }
            }
            $relFilterStr = $relFilterStr . " ) ";
        }
        $sSchemataFilterStr = "";
        if (count($sSchemata) > 0) {
            $sSchemataFilterStr =' ( ?s_schema = <' . $sSchemata[0] . '>';
            
            for ($i = 1; $i < count($sSchemata); $i++) {
                $sSchemataFilterStr =$sSchemataFilterStr . ' || ?s_schema = <' . $sSchemata[$i] . '>';
            }
        $sSchemataFilterStr = $sSchemataFilterStr . " ) ";    
        }
        $tSchemataFilterStr = "";
        if (count($tSchemata) > 0) {
            $tSchemataFilterStr =' ( ?o_schema = <' . $tSchemata[0] . '>';
            
            for ($i = 1; $i < count($tSchemata); $i++) {
                $tSchemataFilterStr =$tSchemataFilterStr. ' || ?o_schema = <' . $tSchemata[$i] . '>';
            }
        $tSchemataFilterStr = $tSchemataFilterStr . " ) "; 
        }
        $filterStr = "";
        if ($relFilterStr !== "") {
            $filterStr = " filter ( " . $relFilterStr;
            if ($sSchemataFilterStr !== "") {
                $filterStr = $filterStr . " && " . $sSchemataFilterStr;
            }
            if ($tSchemataFilterStr !== "") {
                $filterStr = $filterStr . " && " . $tSchemataFilterStr . ")";
            } else {
                $filterStr = $filterStr . ")";
            }
        } else {
            if ($sSchemataFilterStr !== "") {
                $filterStr = " filter ( " . $sSchemataFilterStr;
                if ($tSchemataFilterStr !== "") {
                    $filterStr = $filterStr . " && " . $tSchemataFilterStr . ")";
                } else {
                    $filterStr = $filterStr . ")";
                }
            } else {
                if ($tSchemataFilterStr !== "") {
                    $filterStr = " filter ( " . $tSchemataFilterStr . ")";
                }
            }
        }
       
        $sparqlQuery = 'select ?rel ?s_uuid ?s_prefLabel ?s_schema ?o_uuid ?o_prefLabel ?o_schema where {?s ?rel ?o; <http://www.w3.org/2004/02/skos/core#prefLabel> ?s_prefLabel; <http://openskos.org/xmlns#uuid> ?s_uuid; <http://www.w3.org/2004/02/skos/core#inScheme> ?s_schema . ?o <http://www.w3.org/2004/02/skos/core#prefLabel> ?o_prefLabel; <http://openskos.org/xmlns#uuid> ?o_uuid; <http://www.w3.org/2004/02/skos/core#inScheme> ?o_schema . ' . $filterStr . '}';
        //\Tools\Logging::var_error_log(" Query \n", $sparqlQuery, '/app/data/Logger.txt');
        $resource = $this->query($sparqlQuery);
        return $resource;
    }
    
    
    // outpu is a list of related concepts
      public function fetchRelationsForConcept($uri, $relationType, $conceptScheme = null) {

        $allRelations = new ConceptCollection([]);

        if (!$uri instanceof Uri) {
            $uri = new Uri($uri);
        }

        $patterns = [
            [$uri, $relationType, '?subject'],
        ];

        if (!empty($conceptScheme)) {
            $patterns[Skos::INSCHEME] = new Uri($conceptScheme);
        }

        $start = 0;
        //fetch($simplePatterns = [], $offset = null, $limit = null, $ignoreDeleted = false, $resType=null)
        $relations = $this->fetch($patterns, $start, MAXIMAL_ROWS, false, new Uri(Concept::TYPE));
        foreach ($relations as $relation) {
            $allRelations->append($relation);
        }

        return $allRelations;
    }

    /**
     * Add relations to a skos concept
     *
     * @param string $uri
     * @param string $relationType
     * @param array|string $uris
     * @throws Exception\InvalidArgumentException
     */
    public function addRelation($uri, $relationType, $uris)
    {
        if (!in_array($relationType, RelationManager::fetchUris(), true)) {
            throw new Exception\InvalidArgumentException('Relation type not supported: ' . $relationType);
        }
        
        // @TODO Add check everywhere we may need it.
        if (in_array($relationType, [Skos::BROADERTRANSITIVE, Skos::NARROWERTRANSITIVE])) {
            throw new Exception\InvalidArgumentException(
                'Relation type "' . $relationType . '" will be inferred. Not supported explicitly.'
            );
        }

        $graph = new \EasyRdf\Graph();
        
        if (!is_array($uris)) {
            $uris = [$uris];
        }
        foreach ($uris as $related) {
            $graph->addResource($uri, $relationType, $related);
        }

        $this->client->insert($graph);
    }
    
    /**
     * Delete relations between two skos concepts.
     * Deletes in both directions (narrower and broader for example).
     * @param string $subjectUri
     * @param string $relationType
     * @param string $objectUri
     * @throws Exception\InvalidArgumentException
     */
    public function deleteRelation($subjectUri, $relationType, $objectUri)
    {
        if (!in_array($relationType, RelationManager::fetchUris(), true)) {
            throw new Exception\InvalidArgumentException('Relation type not supported: ' . $relationType);
        }

        $this->deleteMatchingTriples(
            new Uri($subjectUri),
            $relationType,
            new Uri($objectUri)
        );
        
        $this->deleteMatchingTriples(
            new Uri($objectUri),
            Skos::getInverseRelationsMap()[$relationType],
            new Uri($subjectUri)
        );
        
    }
    
      //TODO: check conditions when a relation definition  can be deleted
    public function CanBeDeleted($uri){
        return parent::CanBeDeleted($uri);
    }
    
}
