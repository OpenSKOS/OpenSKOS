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
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\MyInstitutionModules\RelationTypes;

require_once dirname(__FILE__) . '/config.inc.php';

class RelationTypeManager extends ResourceManager
{

    /**
     * What is the basic resource for this manager.
     * @var string NULL means any resource.
     */
    protected $resourceType = RelationType::TYPE;

    public static function fetchConceptConceptRelationsNameUri()
    {
        $uris = Skos::getSkosConceptConceptRelations();
        $skosrels = [];
        foreach ($uris as $uri) {
            $border = strrpos($uri, "#");
            $name = 'skos:' . substr($uri, $border + 1);
            $skosrels[$name] = $uri;
        }
        $userrels = RelationTypes::$myrelations;
        $result = array_merge($skosrels, $userrels);
        return $result;
    }

    public function fetchAllConceptConceptRelationsOfType($relationType, $sourceSchemata, $targetSchemata)
    {
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
        $relFilterStr = "";
        $existingRelations = array_merge(Skos::getSkosConceptConceptRelations(), $this->getNonSKOSRelationTypes());

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
            $sSchemataFilterStr = ' ( ?s_schema = <' . $sSchemata[0] . '>';

            for ($i = 1; $i < count($sSchemata); $i++) {
                $sSchemataFilterStr = $sSchemataFilterStr . ' || ?s_schema = <' . $sSchemata[$i] . '>';
            }
            $sSchemataFilterStr = $sSchemataFilterStr . " ) ";
        }
        $tSchemataFilterStr = "";
        if (count($tSchemata) > 0) {
            $tSchemataFilterStr = ' ( ?o_schema = <' . $tSchemata[0] . '>';

            for ($i = 1; $i < count($tSchemata); $i++) {
                $tSchemataFilterStr = $tSchemataFilterStr . ' || ?o_schema = <' . $tSchemata[$i] . '>';
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

        $sparqlQuery = 'select distinct ?rel ?s_uuid ?s_prefLabel ?s_schema ?s_schema_title ?o_uuid ?o_prefLabel ?o_schema ?o_schema_title where {?s ?rel ?o. ?s <http://www.w3.org/2004/02/skos/core#prefLabel> ?s_prefLabel. ?s <http://openskos.org/xmlns#uuid> ?s_uuid. ?s <http://www.w3.org/2004/02/skos/core#inScheme> ?s_schema . ?s_schema <http://purl.org/dc/terms/title> ?s_schema_title . ?o <http://www.w3.org/2004/02/skos/core#prefLabel> ?o_prefLabel. ?o <http://openskos.org/xmlns#uuid> ?o_uuid. ?o <http://www.w3.org/2004/02/skos/core#inScheme> ?o_schema . ?o_schema <http://purl.org/dc/terms/title> ?o_schema_title' . $filterStr . '}';
        //\Tools\Logging::var_error_log(" Query \n", $sparqlQuery, APPLICATION_BASE_PATH.'/data/Logger.txt');
        $resource = $this->query($sparqlQuery);
        return $resource;
    }

    public function createOutputRelationTriples($response)
    {
        $result = [];
        foreach ($response as $key => $value) {
            $subject = array("uuid" => $value->s_uuid->getValue(), "prefLabel" => $value->s_prefLabel->getValue(), "lang" => $value->s_prefLabel->getLang(), "schema_title" => $value->s_schema_title->getValue(), "schema_uri" => $value->s_schema->getUri());
            $object = array("uuid" => $value->o_uuid->getValue(), "prefLabel" => $value->o_prefLabel->getValue(), "lang" => $value->o_prefLabel->getLang(), "schema_title" => $value->o_schema_title->getValue(), "schema_uri" => $value->o_schema->getUri());
            $triple = array("s" => $subject, "p" => $value->rel->getUri(), "o" => $object);
            array_push($result, $triple);
        }
        return $result;
    }
}
