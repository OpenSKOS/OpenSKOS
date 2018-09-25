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
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Namespaces\Rdf;
use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Rdf\Literal;
use OpenSkos2\Rdf\ResourceCollection;
use OpenSkos2\Rdf\Serializer\NTriple;
use OpenSkos2\Rdf\Uri;
use OpenSkos2\Rdf\Resource;
use OpenSkos2\Set;

class SetManager extends ResourceManager
{

    /**
     * What is the basic resource for this manager.
     * @var string NULL means any resource.
     */
    protected $resourceType = Set::TYPE;


    /**
     * Fetches full resources.
     * @param Object[] $simplePatterns Example: [Skos::NOTATION => new Literal('AM002'),]
     * @param int $offset
     * @param int $limit
     * @param bool $ignoreDeleted Do not fetch resources which have openskos:status deleted.
     * @return ResourceCollection
     */
    public function fetch($simplePatterns = [], $offset = null, $limit = null, $ignoreDeleted = false)
    {
        /*
         * This function is a work around for the fact the query that resourceManager->fetch generates takes up
         *  to 5 minutes on the Beeld en Geluid Jena server.
         * After much trial and error, it seems the best result is to fetch all sets and then filter out the tenants
         *  in PHP
         *
         */
        //Of course, the function $this->getAllSets doesn't actually fetch all sets. So we do it here :-(
        $query = <<<FETCH_ALL_SETS
DESCRIBE ?subject
{
  SELECT ?subject
  WHERE{
    ?subject a <%s>
  }
  ORDER BY ?subject
}
FETCH_ALL_SETS;
        $query = sprintf($query, $this->resourceType);
        $resources = $this->fetchQuery($query);

        $tenantCode = $simplePatterns[\OpenSkos2\Namespaces\OpenSkos::TENANT]->getValue();

        $resultSet = array();
        foreach ($resources as $res) {
            $resTenantCode  = $res->getProperty(\OpenSkos2\Namespaces\OpenSkos::TENANT)[0]->getValue();
            if ($resTenantCode === $tenantCode) {
                $resultSet[] = $res;
            }
        }
        return $resultSet;
    }


    //TODO: check conditions when it can be deleted
    public function canBeDeleted($uri)
    {
        return parent::CanBeDeleted($uri);
    }

    public function fetchAllSets($allowOAI)
    {
        $query = 'PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>'
            . 'DESCRIBE ?s {'
            . 'select ?s where {?s <'.OpenSkos::ALLOW_OAI.'>  "' . $allowOAI . '"^^xsd:bool . } }';
        $sets = $this->fetchQuery($query);
        return $sets;
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
        throw new \Exception("Please use the function `fetchAllSets'");
        $query = 'PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>'
            . 'DESCRIBE ?s {'
            . 'select ?s where {?s <'.OpenSkos::ALLOW_OAI.'>  "' . $allowOAI . '"^^xsd:bool . } }';
        $sets = $this->fetchQuery($query);
        return $sets;
    }

    public function getUrisMap($tenantCode)
    {
        $query = <<<QUERY_SETS
        DESCRIBE ?subject {
            SELECT ?subject  WHERE {
                ?subject <%s> <%s>  .
                ?subject <%s>  "%s"
            } 
        }
QUERY_SETS;
        $query = sprintf($query, Rdf::TYPE, Set::TYPE, OpenSkos::TENANT, $tenantCode);
        $query = preg_replace('/\s+/', ' ', $query);
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

    /*
     * Is this dead code
    public function translateMySqlCollectionToRdfSet($collectionMySQL)
    {
        $setResource = new Set();
        if (!isset($collectionMySQL['uri'])) {
            $setResource->setUri('http://unset_uri_in_mysqldatabase');
        } else {
            $setResource->setUri($collectionMySQL['uri']);
        }

        $this->setLiteralWithEmptinessCheck($setResource, OpenSkos::CODE, $collectionMySQL['code']);
        $this->setLiteralWithEmptinessCheck($setResource, DcTerms::PUBLISHER, $collectionMySQL['tenant']);
        $this->setLiteralWithEmptinessCheck($setResource, DcTerms::TITLE, $collectionMySQL['dc_title']);
        $this->setUriWithEmptinessCheck($setResource, OpenSkos::WEBPAGE, $collectionMySQL['website']);
        $this->setUriWithEmptinessCheck($setResource, DcTerms::LICENSE, $collectionMySQL['license_url']);
        $this->setUriWithEmptinessCheck($setResource, OpenSkos::OAI_BASEURL, $collectionMySQL['OAI_baseURL']);
        $this->setBooleanLiteralWithEmptinessCheck($setResource, OpenSkos::ALLOW_OAI, $collectionMySQL['allow_oai']);
        $this->setUriWithEmptinessCheck($setResource, OpenSkos::CONCEPTBASEURI, $collectionMySQL['conceptsBaseUrl']);
        return $setResource;
    }
     */

   
    
   
    public function fetchNameSearchID() // title -> code for sets
    {
        $query = 'SELECT ?name ?searchid WHERE { ?uri  <' . DcTerms::TITLE . '> ?name . '
        . '?uri  <' . OpenSkos::CODE . '> ?searchid . '
        . '?uri  <' . Rdf::TYPE . '> <'.Set::TYPE.'> .}';
        $response = $this->query($query);
        $result = $this->makeNameSearchIDMap($response);
        return $result;
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
    
    public function fetchSetTitleAndCodeByUri($uri)
    {
        $query = "SELECT ?title ?code WHERE { <$uri>  <".DcTerms::TITLE."> ?title . "
         . "<$uri> <".OpenSkos::CODE . "> ?code . "
        . "<$uri> <".Rdf::TYPE . "> <".Set::TYPE."> . }";
        $response = $this->query($query);
        if (count($response)>1) {
            throw new \Exception("Something went very wrong: there more than 1 set with the uri $uri");
        }
        if (count($response)<1) {
            throw new \Exception("the institution with the uri $uri is not found");
        }
        $retval = [];
        $retval['code'] = $response[0]->code->getValue();
        $retval['title'] = $response[0]->title->getValue();
        return $retval;
    }
    /**
     * Gets map with uri as key and title as value.
     *
     * @param string $tenant
     * @return array
     */
    public function getUriToTitleMap($tenant)
    {
        $sets = $this->fetchAll($this->select()->where('tenant=?', $tenant));
        $setsMap = array();
        foreach ($sets as $set) {
            $setsMap[(string)$set->getUri()] = $set->dc_title;
        }
        return $setsMap;
    }
}
