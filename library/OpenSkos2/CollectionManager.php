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

use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Namespaces\Rdf;
use OpenSkos2\Rdf\Literal;
use OpenSkos2\Rdf\ResourceCollection;
use OpenSkos2\Rdf\ResourceManager;
use OpenSkos2\Rdf\Serializer\NTriple;
use OpenSkos2\Rdf\Uri;
use OpenSkos2\Rdf\Resource;

class CollectionManager extends ResourceManager
{

    /**
     * What is the basic resource for this manager.
     * @var string NULL means any resource.
     */
    protected $resourceType = Collection::TYPE;

    public function __call($name, $arguments)
    {
        if($name === 'fetchAllSets'){
            return $this->fetchAllCollections($arguments[0]);
        }
        throw new \Exception(
            "unresolvable call $name to ".__NAMESPACE__."/".__CLASS__
        );

    }
    
    /**
     * Soft delete resource , sets the openskos:status to deleted
     * and add a delete date.
     *
     * Be careful you need to add the full resource as it will be deleted and added again
     * do not only give a uri or part of the graph
     *
     * @param \OpenSkos2\Rdf\Resource $resource
     * @param Uri $user
     */
    public function deleteSoft(Resource $resource, Uri $user = null)
    {
        $this->delete($resource);
        
        $resource->setUri(rtrim($resource->getUri(), '/') . '/deleted');
        
        $resource->setProperty(OpenSkos::STATUS, new Literal(\OpenSkos2\Concept::STATUS_DELETED));
        $resource->setProperty(OpenSkos::DATE_DELETED, new Literal(date('c'), null, Literal::TYPE_DATETIME));

        if ($user) {
            $resource->setProperty(OpenSkos::DELETEDBY, $user);
        }

        $this->replace($resource);
    }

    //TODO: check conditions when it can be deleted
    public function canBeDeleted($uri)
    {
        return parent::CanBeDeleted($uri);
    }

    /**
     * Get all scheme's by set URI
     *
     * @param string $setUri e.g http://openskos.org/api/collections/rce:TEST
     * @param array $filterUris
     * @return ResourceCollection
     */
    public function getSchemeBySetUri($setUri, $filterUris = [])
    {
        $uri = new Uri($setUri);
        $escaped = (new NTriple())->serialize($uri);
        $query = 'PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
            PREFIX openskos: <http://openskos.org/xmlns#>
            PREFIX dcterms: <http://purl.org/dc/terms/>
            PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
                SELECT ?subject ?title ?uuid
                WHERE {
                    ?subject rdf:type skos:ConceptScheme;
                    <' . OpenSkos::SET . '> ' . $escaped . ';
                    dcterms:title ?title;
                    openskos:uuid ?uuid;
            ';

        if (!empty($filterUris)) {
            $query .= 'FILTER (?subject = '
                . implode(' || ?subject = ', array_map([$this, 'valueToTurtle'], $filterUris))
                . ')';
        }

        $query .= '}';

        $result = $this->query($query);

        $retVal = new ResourceCollection();
        foreach ($result as $row) {
            $uri = $row->subject->getUri();

            if (empty($uri)) {
                continue;
            }

            $scheme = new ConceptScheme($uri);
            if (!empty($row->title)) {
                $scheme->addProperty(DcTerms::TITLE, new Literal($row->title->getValue()));
            }

            if (!empty($row->uuid)) {
                $scheme->addProperty(\OpenSkos2\Namespaces\OpenSkos::UUID, new Literal($row->uuid->getValue()));
            }

            $scheme->addProperty(\OpenSkos2\Namespaces\OpenSkos::SET, new Uri($setUri));

            $retVal[] = $scheme;
        }

        return $retVal;
    }

    public function fetchAllCollections($allowOAI)
    {
        $query = 'PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>'
            . 'DESCRIBE ?s {'
            . 'select ?s where {?s <'.OpenSkos::ALLOW_OAI.'>  "' . $allowOAI . '"^^xsd:bool . } }';
        $sets = $this->fetchQuery($query);
        return $sets;
    }

    public function getUrisMap($tenantCode)
    {
        $query = 'DESCRIBE ?subject {SELECT DISTINCT ?subject  WHERE '
            . '{ ?subject ?predicate ?object . ?subject <' .
            OpenSkos::TENANT . '>  "'. $tenantCode . '". '
            . ' ?subject <'.Rdf::TYPE.'> <'.Set::TYPE.'> } }';
        $result = $this->query($query);
        $retVal = [];
        foreach ($result as $set) {
            $retVal[$set->getUri()]['uri'] = $set->getUri();
            $code = $set->getCode();
            $retVal[$set->getUri()]['code'] = $code->getValue();
        }
        return $retVal;
    }

        // used only for HTML representation
    public function fetchInhabitantsForSet($setUri, $rdfType)
    {

        $query = "SELECT ?uri ?uuid WHERE  { ?uri  <" . OpenSkos::SET . "> <" . $setUri . "> ."
            . ' ?uri  <' . Rdf::TYPE . '> <' . $rdfType . '> .'
            . ' ?uri  <' . OpenSkos::UUID . '> ?uuid .}';

        $retVal = [];
        $response = $this->query($query);
        foreach ($response as $tuple) {
            $uri = $tuple->uri->getUri();
            $uuid = $tuple->uuid->getValue();
            $retVal[$uri] = $uuid;
        }
        return $retVal;
    }

    public function listConceptsForSet($uri)
    {
        $query = "SELECT ?name ?searchid WHERE {"
            . "?concepturi  <" . OpenSkos::SET . ">  <$uri> . "
            . "?concepturi  <" . Skos::PREFLABEL . "> ?name . "
            . "?concepturi  <" . OpenSkos::UUID . "> ?searchid .}";
        $response = $this->query($query);
        $result = $this->makeNameSearchIDMap($response);
        return $result;
    }


    /**
     * Gets map with uri as key and title as value.
     *
     * @param string $tenant
     * @return array
     */
    public function getUriToTitleMap($tenant)
    {
        $collections = $this->fetchAll($this->select()->where('tenant=?', $tenant));
        $collectionsMap = array();
        foreach ($collections as $collection) {
            $collectionsMap[(string)$collection->getUri()] = $collection->dc_title;
        }
        return $collectionsMap;
    }

}
