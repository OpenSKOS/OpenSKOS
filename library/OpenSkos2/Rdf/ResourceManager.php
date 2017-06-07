<?php

/**
 * OpenSKOS
 * /Users/olha/WorkProjects/open-skos-2/OpenSKOS2tempMeertens/library/OpenSkos2/Rdf/ResourceManager.php
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

namespace OpenSkos2\Rdf;

use Asparagus\QueryBuilder;
use EasyRdf\Http;
use EasyRdf\Sparql\Client;
use OpenSkos2\Bridge\EasyRdf;
use OpenSkos2\Concept;
use OpenSkos2\Exception\ResourceAlreadyExistsException;
use OpenSkos2\Namespaces;
use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Namespaces\OpenSkos as OpenSkosNamespace;
use OpenSkos2\Namespaces\Org;
use OpenSkos2\Namespaces\Owl;
use OpenSkos2\Namespaces\Dcmi;
use OpenSkos2\Namespaces\Foaf;
use OpenSkos2\Namespaces\Rdf as RdfNamespace;
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Namespaces\VCard;
use OpenSkos2\Rdf\Serializer\NTriple;
use OpenSkos2\Rdf\Uri;
use OpenSkos2\Rdf\Literal;
use RuntimeException;
use OpenSkos2\Exception\ResourceNotFoundException;
use OpenSkos2\Solr\ResourceManager as SolrResourceManager;

// @TODO A lot of things can be made without working with full documents, so that should not go through here
// For example getting a list of pref labels and uris
// Mertens: 
// -- for refactored "fetchByUri" and "fecthByUuid" the corresponding versions of
// picturae (23,21/11/2016) cannot be taken,
// see the comment inside the body of "fetchByUuid". Complaint "something went very wrong" is thrown now
// by "fetchBy method", called from both, "fetchByUri" and "fecthByUuid".
// -- other chnages starting from 14/11/2016 are taken

class ResourceManager
{

    /**
     * @var Client
     */
    protected $client;

    /**
     * What is the basic resource for this manager.
     * Made to be extended and overwrited.
     * @var string NULL means any resource.
     */
    protected $resourceType = null;

    /**
     * @var \OpenSkos2\Solr\ResourceManager
     */
    protected $solrResourceManager;
    protected $customRelationTypes;
    protected $init = [];
    protected $infoLog;

    public function getResourceType()
    {
        return $this->resourceType;
    }

    public function getInitArray()
    {
        return $this->init;
    }

    /**
     * Use that if inserting a large amount of resources.
     * Call commit at the end.
     * @return bool
     */
    public function getIsNoCommitMode()
    {
        return $this->solrResourceManager->getIsNoCommitMode();
    }

    /**
     * Use that if inserting a large amount of resources.
     * Call commit at the end.
     * @param bool
     */
    public function setIsNoCommitMode($isNoCommitMode)
    {
        $this->solrResourceManager->setIsNoCommitMode($isNoCommitMode);
    }

    /**
     * @param Client $client
     * @param SolrResourceManager $solrResourceManager
     */
    public function __construct(Client $client, SolrResourceManager $solrResourceManager)
    {
        $this->client = $client;
        $this->solrResourceManager = $solrResourceManager;
        $this->init = parse_ini_file(__DIR__ . '/../../../application/configs/application.ini');
        $this->infoLog = "/../../../" . $this->init["custom.info_log"];
        if ($this->init["custom.default_relationtypes"]) {
            $this->customRelationTypes = null;
        } else {
            $this->customRelationTypes = new \OpenSkos2\Custom\RelationTypes();
        }
    }

    /**
     * @param Resource $resource
     * @throws ResourceAlreadyExistsException
     */
    public function insert(Resource $resource)
    {
        // Put type if we have it and it is missing.
        if (!empty($this->resourceType) && $resource->isPropertyEmpty(RdfNamespace::TYPE)) {
            $resource->setProperty(RdfNamespace::TYPE, new Uri($this->resourceType));
        }
        $graph = EasyRdf::resourceToGraph($resource);
        $this->insertWithRetry($graph);
        // set and tenat are derived (based on shcema) elements for the concept
        // they are not submitted to the triple store
        // however to spead up search we add set and tenant to solr description of the concept
        if ($resource->getType()->getUri() == Concept::TYPE) {
            $specs = $this->fetchConceptSpec($resource);
            if (count($specs) < 1) {
                throw new \Exception("Cannot fetch spec for the concept {$resource->getUri()}");
            }
            foreach ($specs as $spec) {
                $resource->setProperty(OpenSkosNamespace::TENANT, new Uri($spec['tenanturi']));
                $resource->setProperty(OpenSkosNameSpace::SET, new Uri($spec['seturi']));
            }
            $this->solrResourceManager->insert($resource);
        }
    }

    /**
     * Deletes and then inserts the resourse.
     * @param Resource $resource
     */
    public function replace(Resource $resource)
    {
        // @TODO Danger if insert fails. Need transaction or something.
        $this->delete($resource, $resource->getType()->getUri());
        //var_dump($resource);
        $this->insert($resource);
    }

    /**
     * Soft delete resource , sets the openskos:status to deleted
     * and add a delete date.
     *
     * Be careful you need to add the full resource as it will be deleted and added again
     * do not only give a uri or part of the graph
     *
     * @param Resource $resource
     * @param Uri $user
     */
    public function deleteSoft(Resource $resource, Uri $user = null)
    {
        $resource->unsetProperty(OpenSkosNamespace::STATUS);
        $status = new Literal(Resource::STATUS_DELETED);
        $resource->addProperty(OpenSkosNamespace::STATUS, $status);

        $resource->unsetProperty(OpenSkosNamespace::DATE_DELETED);
        $resource->addProperty(OpenSkosNamespace::DATE_DELETED, new Literal(date('c'), null, Literal::TYPE_DATETIME));

        if ($user) {
            $resource->unsetProperty(OpenSkosNamespace::DELETEDBY, $user);
        }

        $this->replace($resource);
    }

    /**
     * @param Uri $resource
     */
    public function delete(Uri $resource, $rdfType = null)
    {
        $this->client->update("DELETE WHERE {<{$resource->getUri()}> ?predicate ?object}");
        if ($rdfType === Skos::CONCEPT) {
            $this->solrResourceManager->delete($resource);
        }
    }

    public function deleteReferencesToObject(Uri $resource)
    {
        $this->client->update("DELETE WHERE {?subject ?predicate  <{$resource->getUri()}> }");
    }

    /**
     * @todo Keep SOLR in sync
     * @param Object[] $simplePatterns
     */
    public function deleteBy($simplePatterns)
    {
        $query = "DELETE WHERE {\n ?subject ";
        foreach ($simplePatterns as $predicate => $value) {
            $query .= "<{$predicate}> " . $this->valueToTurtle($value) . ";\n";
        }
        $query .= "?predicate ?object\n}";

        $this->client->update($query);

        // @TODO remove from solr
    }

    /**
     * Delete all triples where pattern matches
     * @todo Keep SOLR in sync
     * @param Object|string $subject Put "?subject" to match all.
     * @param string $predicate
     * @param Object|string $object Put "?object" to match all.
     */
    public function deleteMatchingTriples($subject, $predicate, $object)
    {
        // @TODO Refactor. Not for resource manager.
        $query = 'DELETE WHERE {' . PHP_EOL;
        $query .= $subject == '?subject' ? '?subject' : $this->valueToTurtle($subject);
        $query .= ' <' . $predicate . '> ';
        $query .= $object == '?object' ? '?object' : $this->valueToTurtle($object);
        $query .= PHP_EOL . '}';
        $this->client->update($query);
    }

    public function fetchResourceFilters()
    {
        $query = 'SELECT DISTINCT ?uri  ?title ?type WHERE '
            . '{ {?uri <' . DcTerms::TITLE . '> ?title . ?uri <' . RdfNamespace::TYPE . '> ?type . '
            . 'FILTER ( ?type = <' . Skos::SKOSCOLLECTION . '> || ?type = <' . Skos::CONCEPTSCHEME .
            '> || ?type = <' . Dcmi::DATASET . '>  ) } '
            . ' UNION { ?uri <' . RdfNamespace::TYPE . '> ?type . '
            . ' ?uri <' . VCard::ORG . '> ?node . ?node <' . VCard::ORGNAME . '> ?title '
            . ' FILTER ( ?type = <' . Org::FORMALORG . '>)} } ';
        $response = $this->query($query);
        $retVal = [];
        $retVal[Skos::SKOSCOLLECTION] = [];
        $retVal[Skos::CONCEPTSCHEME] = [];
        $retVal[Dcmi::DATASET] = [];
        $retVal[Org::FORMALORG] = [];
        foreach ($response as $descr) {
            $spec = [];
            $spec['uri'] = $descr->uri->getUri();
            $spec['title'] = $descr->title->getValue();
            $retVal[$descr->type->getUri()][] = $spec;
        }
        return $retVal;
    }

    public function fetchResourceFiltersForRelations()
    {
        $query = 'SELECT DISTINCT ?uri  ?title ?type WHERE '
            . ' {?uri <' . DcTerms::TITLE . '> ?title . ?uri <' . RdfNamespace::TYPE . '> ?type . '
            . 'FILTER ( ?type = <' . Owl::OBJECT_PROPERTY . '> || ?type = <' . Skos::CONCEPTSCHEME . '> )} ';
        $response = $this->query($query);
        $retVal = [];
        $retVal[Owl::OBJECT_PROPERTY] = [];
        $retVal[Skos::CONCEPTSCHEME] = [];
        foreach ($response as $descr) {
            $spec = [];
            $spec['uri'] = $descr->uri->getUri();
            $spec['title'] = $descr->title->getValue();
            $retVal[$descr->type->getUri()][] = $spec;
        }
        $skosrels = Skos::getSkosConceptConceptRelations();
        $len = strlen(Skos::NAME_SPACE);
        foreach ($skosrels as $skosrel) {
            $spec = [];
            $spec['uri'] = $skosrel;
            $spec['title'] = 'skos:' . substr($skosrel, $len);
            $retVal[Owl::OBJECT_PROPERTY][] = $spec;
        }
        return $retVal;
    }

    /**
     * Fetch resource by uuid
     *
     * @param string $uuid
     * @return Resource
     * @throws ResourceNotFoundException
     */
    public function fetchByUuid($uuid, $resType = null)
    {
        // Meertens: The request below using FILTER NOT EXIST is necessary because
        // institutions have "nested" structure,
        // that is they have sub-elements (address and institution description)
        // which have internal node id-s and are rdf-s on itself.
        // The difference between them and regular rdf resources is that they do not have types


        $query = 'DESCRIBE ?subject ?object '
            . '{SELECT DISTINCT ?subject  ?object WHERE '
            . '{ ?subject <' . OpenSkosNameSpace::UUID . '> "' . $uuid .
            '". ?subject ?predicate ?object . FILTER NOT EXISTS { ?object <' .
            RdfNamespace::TYPE . '> ?type } } }';
        $result = $this->fetchBy($query, $uuid, $resType);
        return $result;

        /* picturae version:
          $prefixes = [
          'openskos' => OpenSkosNamespace::NAME_SPACE,
          ];

          $lit = new \OpenSkos2\Rdf\Literal($uuid);
          $qb = new \Asparagus\QueryBuilder($prefixes);
          $query = $qb->describe('?subject')
          ->where('?subject', 'openskos:uuid', (new \OpenSkos2\Rdf\Serializer\NTriple)->serialize($lit));
          $data = $this->fetchQuery($query);

          if (count($data) == 0) {
          throw new ResourceNotFoundException(
          'The requested resource with openskos::uuid <' . $uuid . '> was not found.'
          );
          }

          if (count($data) > 1) {
          throw new \RuntimeException(
          'Something went very wrong. The requested resource with uuid <' . $uuid . '> was found more than once.'
          );
          }

          return $data[0];
         * */
    }

    /**
     * Fetches a single resource matching the uri.
     * @param string $uri
     * @return Resource
     */
    public function fetchByUri($uri, $resType = null)
    {
        $query = 'DESCRIBE ?subject ?object {SELECT DISTINCT ?subject  ?object WHERE '
            . '{ ?subject ?predicate ?object . FILTER (?subject=<' . $uri . '>) . '
            . ' FILTER NOT EXISTS { ?object <' . RdfNamespace::TYPE . '> ?type } } }';
        $result = $this->fetchBy($query, $uri, $resType);
        return $result;
    }

    /*
     * Fetch resource by code
     *
     * @param string $code
     * @return Resource
     */

    public function fetchByCode($code, $resType = null)
    {
        $query = 'DESCRIBE ?subject ?object '
            . '{SELECT DISTINCT ?subject  ?object WHERE '
            . '{ ?subject <' . OpenSkosNamespace::CODE . '> "' .
            $code . '". ?subject ?predicate ?object . FILTER NOT EXISTS { ?object <' .
            RdfNamespace::TYPE . '> ?type } } }';
        $result = $this->fetchBy($query, $code, $resType);
        return $result;
    }

    private function fetchBy($query, $reference, $resType = null)
    {
        if ($resType === null) {
            $resType = $this->getResourceType();
        }

        try {
            $result = $this->query($query);
            $resources = EasyRdf::graphToResourceCollection($result, $resType);
            // @TODO Add resourceType check.
        } catch (\Exception $exp) {
            throw new ResourceNotFoundException("Unable to fetch resource, " . $exp->getMEesage());
        }


        if (count($resources) === 0) {
            throw new ResourceNotFoundException(
            "The requested resource with reference  $reference . of type $resType is not found."
            );
        }

        if (count($resources) > 1) {
            throw new \RuntimeException(
            "Something went very wrong. The requested resource $reference of type $resType was found more than once."
            );
        }

        return $resources[0];
    }

    public function fetchSubjectTypePropertyForObject($objectUri)
    {
        $query = 'SELECT ?subject  ?type ?property WHERE '
            . '{  ?subject <' . RdfNamespace::TYPE . '> ?type . ?subject ?property <' . $objectUri . '> .}';
        $result = $this->query($query);
        return $result;
    }

    /**
     * Fetches multiple records by list of uris.
     * @param string[] $uris
     * @return ResourceCollection
     */
    public function fetchByUris($uris, $resType = null)
    {
        /*
          DESCRIBE ?subject
          WHERE {
          ?subject ?predicate ?object .
          FILTER (
          ?subject = <http://data.beeldengeluid.nl/gtaa/135633>
          || ?subject = <http://data.beeldengeluid.nl/gtaa/350064>
          )
          }
         */

        if ($resType === null) {
            $resType = $this->getResourceType();
        }

        $resources = EasyRdf::createResourceCollection($resType);

        if (!empty($uris)) {
            foreach (array_chunk($uris, 50) as $urisChunk) {
                $filters = [];
                foreach ($urisChunk as $uri) {
                    $filters[] = '?subject = ' . $this->valueToTurtle(new Uri($uri));
                }

                $query = new QueryBuilder();
                $query->describe('?subject')
                    ->where('?subject', '?predicate', '?object')
                    ->filter(implode(' || ', $filters));

                if (!empty($resType)) {
                    $query->where('?subject', '<' . RdfNamespace::TYPE . '>', '<' . $resType . '>');
                }

                foreach ($this->fetchQuery($query) as $resource) {
                    $resources->append($resource);
                }
            }

            // Keep the ordering of the passed uris.
            $resources->uasort(function (Resource $resource1, Resource $resource2) use ($uris) {
                $searchUris = array_values($uris);
                $ind1 = array_search($resource1->getUri(), $searchUris);
                $ind2 = array_search($resource2->getUri(), $searchUris);
                return $ind1 - $ind2;
            });
        }
        return $resources;
    }

    /**
     * Asks if a resource with the given uri exists.
     * @param string $uri
     * @param bool $checkAllResourceTypes
     * @return bool
     */
    public function askForUri($uri, $checkAllResourceTypes = false)
    {
        $query = '<' . $uri . '> ?predicate ?object';

        if (!$checkAllResourceTypes && !empty($this->resourceType)) {
            $query .= ' . ';
            $query .= '<' . $uri . '> <' . RdfNamespace::TYPE . '> <' . $this->resourceType . '>';
        }

        return $this->ask($query);
    }

    /**
     * Fetches full resources.
     * There is hardcoded order by uri.
     * @param Object[] $simplePatterns Example: [Skos::NOTATION => new Literal('AM002'),]
     * @param int $offset
     * @param int $limit
     * @param bool $ignoreDeleted Do not fetch resources which have openskos:status deleted.
     * @return ResourceCollection
     */
    public function fetch($simplePatterns = [], $offset = null, $limit = null, $ignoreDeleted = false, $resType = null)
    {
        /*
          DESCRIBE ?subject {
          SELECT DISTINCT ?subject
          WHERE {
          ?subject ?predicate ?object
          }
          ORDER BY ?subject
          LIMIT 10
          OFFSET 0
          }
         */


        $newpatterns[RdfNamespace::TYPE] = null;
        if ($resType === null) {
            $resType = $this->resourceType;
            if (!empty($resType)) {
                $newPatterns[RdfNamespace::TYPE] = new Uri($resType);
            }
        } else {
            $newPatterns[RdfNamespace::TYPE] = $resType;
        };
        if ($newPatterns[RdfNamespace::TYPE] !== null) {
            if ($newPatterns[RdfNamespace::TYPE]->getUri() === \OpenSkos2\Namespaces\Skos::CONCEPTSCHEME) {
                $simplePatterns = array_merge($newPatterns, $simplePatterns);
            } else {
                $simplePatterns = array_merge($simplePatterns, $newPatterns);
            }
        };


        $query = 'DESCRIBE ?subject ?object {' . PHP_EOL;

        $query .= 'SELECT DISTINCT ?subject ?object ' . PHP_EOL;
        $where = $this->simplePatternsToQuery($simplePatterns, '?subject');
        $where .= '?subject ?property ?object . ';


        if ($ignoreDeleted) {
            $where .= 'OPTIONAL { ?subject <' . OpenSkosNamespace::STATUS . '> ?status } . ';
            $where .= 'FILTER (!bound(?status) || ?status != \'' . Resource::STATUS_DELETED . '\')';
        }

        $query .= 'WHERE { ' . $where . '}';
        // We need some order
        // @TODO provide possibility to order on other predicates.
        // This will need to create ?subject ?predicate ?o1 .... ORDER BY ?o1
        $query .= PHP_EOL . 'ORDER BY ?subject';

        if ($limit !== null) {
            $query .= PHP_EOL . 'LIMIT ' . $limit;
        }

        if ($offset !== null) {
            $query .= PHP_EOL . 'OFFSET ' . $offset;
        }

        $query .= '}'; // end sub select
        $resources = $this->fetchQuery($query, $resType);
        // The order by part does not apply to the resources with describe.
        // So we need to order them again.
        // @TODO Find other solution - sort in jena, not here.
        // @TODO provide possibility to order on other predicates.
        $resources->uasort(
            function (Resource $resource1, Resource $resource2) {
            return strcmp($resource1->getUri(), $resource2->getUri());
        }
        );

        return $resources;
    }

    /**
     * Fetch list of namespaces which are used among the resources in the database.
     * @return ResourceCollection
     */
    public function fetchNamespaces()
    {
        // @TODO Picturae's remark: Not working, see \OpenSkos2\Namespaces::getRdfConceptNamespaces()

        return Namespaces::getRdfConceptNamespaces();

        $query = 'DESCRIBE ?subject';
        $query .= PHP_EOL . ' LIMIT 0';

        // The EasyRdf\Sparql\Client does not gets the namespaces which fuseki provides.
        // Maybe it can be fixed/configured. Then this method can use the client directly.
        // @TODO DI
        $httpClient = Http::getDefaultHttpClient();
        $httpClient->resetParameters();

        $httpClient->setMethod('GET'); // @TODO Post for big queries
        $uri = $this->client->getQueryUri() . '?query=' . urlencode($query) . '&format=json';
        $httpClient->setUri($uri);

        $response = $httpClient->request();

        if (!$response->isSuccessful()) {
            throw new RuntimeException(
            'HTTP request to ' . $uri . ' for getting namespaces failed: ' . $response->getBody()
            );
        }

        return json_decode($response->getBody(), true)['@context'];
    }

    /**
     * Counts distinct resources
     * @param Object[] $simplePatterns Example: [Skos::NOTATION => new Literal('AM002'),]
     * @return int
     */
    public function countResources($simplePatterns = [])
    {
        $query = 'SELECT (COUNT(DISTINCT ?subject) AS ?count)' . PHP_EOL;
        $query .= 'WHERE { ' . $this->simplePatternsToQuery($simplePatterns, '?subject') . ' }';

        /* @var $result \EasyRdf\Sparql\Result */
        $result = $this->query($query);

        return $result->count->getValue();
    }

    public function countTriples($subject, $prop, $object)
    {

        $query = 'SELECT (COUNT(*) as ?COUNT) WHERE { ' . $subject . '  ' . $prop . '  ' . $object . ' . }';
        $result = $this->query($query);
        $retVal = $result[0]->COUNT->getValue();
        return $retVal;
    }

    public function resourceExists($id, $rdfType)
    {

        if ($rdfType == Concept::TYPE) {
            if ($id !== null && isset($id)) {
                if (substr($id, 0, 7) === "http://" || substr($id, 0, 8) === "https://") {
                    $query = 'uri:"' . $id . '"';
                } else {
                    $query = 'uuid:' . $id;
                }
                $solr_result = $this->solrResourceManager->search($query);
                return (count($solr_result) > 0);
            } else {
                return false;
            }
        } else {
            if ($id !== null && isset($id)) {
                if ($rdfType == null) {
                    throw new \Exception('No rdf type is given for resource with id ' . $id);
                }
                if (substr($id, 0, 7) === "http://" || substr($id, 0, 8) === "https://") {
                    $count = $this->countTriples('<' . $id . '>', '<' . RdfNamespace::TYPE . '>', '<' . $rdfType . '>');
                    return ($count > 0);
                } else {
                    $subjectURIs = $this->fetchSubjectWithPropertyGiven(
                        OpenSkosNamespace::UUID, '"' . $id . '"', $rdfType
                    );
                    return (count($subjectURIs) > 0);
                }
            } else {
                return false;
            }
        }
    }

    public function fetchSubjectWithPropertyGiven($propertyUri, $value, $rdfType = null)
    {
        $typeFilter = "";
        if (isset($rdfType)) {
            $typeFilter = ' ?subject <' . RdfNamespace::TYPE . '> <' . $rdfType . '> . ';
        }
        $query = 'SELECT DISTINCT ?subject WHERE { ?subject  <' . $propertyUri . '> ' .
            $value . ' . ' . $typeFilter . '}';
        $result = $this->query($query);
        //var_dump($query);
        $retVal = $this->makeListOfPrimitiveResults($result, 'subject');
        return $retVal;
    }

    public function fetchSubjectUriForUriRdfObject($resource, $property, $value)
    {
        $uri = $value->getUri();
        $types = $resource->getProperty(RdfNamespace::TYPE);
        $rdfType = $types[0]->getUri();
        if ($rdfType === Concept::TYPE) {
            $split = explode("#", $property);
            $field = $split[1];
            $solrQuery = 's_' . $field . ':"' . $uri . '"';
            $docs = $this->solrResourceManager->search($solrQuery);
            return $this->makeUriListFromSolrResponse($docs);
        } else {
            $retVal = $this->fetchSubjectWithPropertyGiven($property, '<' . $uri . '>', $rdfType);
            return $retVal;
        }
    }

    public function fetchSubjectUriForLiteralRdfObject($resource, $property, $value)
    {
        $language = $value->getLanguage();
        $term = $value->getValue();
        $types = $resource->getProperty(RdfNamespace::TYPE);
        $rdfType = $types[0]->getUri();
        if ($rdfType === Concept::TYPE && $property !== OpenSkosNamespace::UUID) {
// solr request, works only for skos and open-skos properties
            $split = explode("#", $property);
            $field = $split[1];
            $solrQuery = 's_' . $field . ':"' . $term . '"';
            if ($field === 'prefLabel' || $field === 'altLabel' || $field === 'hiddenLabel' || $field = 'notation') {
                $schemes = $resource->getProperty(Skos::INSCHEME);
                $n = count($schemes);
                if ($n > 0) {
                    $solrSchemes = ' AND inScheme:(';
                    if ($n > 1) {
                        for ($i = 0; $i < $n - 1; $i++) {
                            $solrSchemes.= '"' . $schemes[$i]->getUri() . '" OR ';
                        }
                    }
                    $solrSchemes.= '"' . $schemes[$n - 1]->getUri() . '")';
                    $solrQuery.= $solrSchemes;
                }
            }
            $docs = $this->solrResourceManager->search($solrQuery);
            return $this->makeUriListFromSolrResponse($docs);
        } else { // triple store request
            if ($language !== null && $language !== '') {
                $completeValue = '"' . $term . '"@' . $language;
            } else {
                $completeValue = '"' . $term . '"';
            }
            $retVal = $this->fetchSubjectWithPropertyGiven($property, $completeValue, $rdfType);
            return $retVal;
        }
    }

    private function makeUriListFromSolrResponse($docs)
    {
        $retVal = [];
        foreach ($docs as $doc) {
            $retVal[] = $doc;
        }
        return $retVal;
    }

    public function fetchObjectsWithProperty($propertyUri, $rdfType = null)
    {
        $typeFilter = "";
        if (isset($rdfType)) {
            $typeFilter = ' ?subject <' . RdfNamespace::TYPE . '> <' . $rdfType . '> . ';
        }
        $query = 'SELECT DISTINCT ?object WHERE { ?subject  <' . $propertyUri . '> ?object . ' . $typeFilter . '}';

        $result = $this->query($query);
        $retVal = $this->makeListOfPrimitiveResults($result, 'object');
        return $retVal;
    }

    public function fetchObjectForSubjectAndProperty($subjectUri, $propertyUri)
    {

        $query = 'SELECT DISTINCT ?object WHERE { <' . $subjectUri . '> <' . $propertyUri . '> ?object . }';

        $result = $this->query($query);
        $retVal = $this->makeListOfPrimitiveResults($result, 'object');
        return $retVal;
    }

    public function fetchUsersInstitution($userUri)
    {
        $query = 'SELECT DISTINCT ?object WHERE { <' . $userUri . '>  <' . Foaf::ORGANISATION . '> ?object . }';
        $result = $this->query($query);
        if (count($result) > 0) {
            $retVal = $result[0]->object->getUri();
        } else {
            $retVal = null;
        }
        return $retVal;
    }

    private function makeListOfPrimitiveResults($sparqlQueryResult, $triplePart)
    {
        $items = [];
        foreach ($sparqlQueryResult as $resource) {
            $className = get_class($resource->$triplePart);
            if ('EasyRdf\Literal' === $className) {
                $items[] = $resource->$triplePart->getValue();
            } else {
                $items[] = $resource->$triplePart->getUri();
            }
        }
        return $items;
    }

    protected function makeNameUriMap($sparqlQueryResult)
    {
        $items = [];
        foreach ($sparqlQueryResult as $resource) {
            $uri = $resource->uri->getUri();
            $name = $resource->name->getValue();
            $items[$name] = $uri;
        }
        return $items;
    }

    /* Returns a map, mapping resource's titles to the resource's Uri
     * Works for set, schema, skos colllection, user relation definition
     */

    public function fetchNameUri()
    {
        $query = 'SELECT ?uri ?name WHERE { ?uri  <' . DcTerms::TITLE . '> ?name .  ?uri  <' .
            RdfNamespace::TYPE . '> <' . $this->getResourceType() . '> .}';
        $response = $this->query($query);
        $result = $this->makeNameUriMap($response);
        return $result;
    }

    /**
     * Asks for if the properties map has a match.
     * Example for $matchProperties:
     *
     * <code>
     * $matchProperties = [
     *     [
     *       "predicate" => Skos::NOTATION
     *       "value" => $concept->getProperty(Skos::NOTATION),
     *       "operator" => "=" // optional defaults to equals
     *     ],
     *     [
     *       "predicate" => Skos::INSCHEME
     *       "value" => $concept->getProperty(Skos::INSCHEME),
     *       "operator" => "!="
     *     ]
     * ];
     * </code>
     *
     * @param array $matchProperties
     * @param string $excludeUri
     * @param bool $ignoreDeleted
     * @return boolean
     */
    public function askForMatch(array $matchProperties, $excludeUri = null, $ignoreDeleted = true)
    {
        $select = '';
        $filter = 'FILTER(' . PHP_EOL;

        if (!empty($this->resourceType)) {
            $matchProperties[] = [
                'predicate' => RdfNamespace::TYPE,
                'value' => new Uri($this->resourceType),
            ];
        }

        $filters = [];
        foreach ($matchProperties as $i => $data) {
            $predicate = $data['predicate'];
            $operator = '=';

            if (isset($data['operator'])) {
                $operator = $data['operator'];
            }

            $select .= '?subject <' . $predicate . '> ?' . $i . '. ' . PHP_EOL;

            $value = $data['value'];
            if (!is_array($value)) {
                $value = [$value];
            }

            $newFilter = [];
            foreach ($value as $val) {
                $object = '?' . $i;
                if (isset($data['ignoreLanguage']) && $data['ignoreLanguage']) {
                    // Get only the simple string literal to compare without language.
                    $object = 'str(' . $object . ')';
                }

                $newFilter[] = $object . ' ' . $operator . ' ' . (new NTriple())->serialize($val);
            }

            $filters[] = '(' . implode(' || ', $newFilter) . ') ';
        }

        if ($ignoreDeleted) {
            $select .= '?subject <' . OpenSkosNamespace::STATUS . '> ?status. ' . PHP_EOL;
            $filters[] = '(!bound(?status) || ?status != \'' . Resource::STATUS_DELETED . '\')';
        }

        $filter .= implode(' && ', $filters) . ' ';

        if ($excludeUri) {
            $uri = new Uri($excludeUri);
            $filter .= '&& ?subject != ' . (new NTriple())->serialize($uri);
        }

        $ask = $select . $filter . ')';

        return $this->ask($ask);
    }

    /**
     * Fetch all resources matching the query.
     *
     * @param QueryBuilder|string $query
     * @return ResourceCollection
     */
    public function fetchQuery($query, $resType = null)
    {
        if ($query instanceof QueryBuilder) {
            $query = $query->getSPARQL();
        }
        $result = $this->query($query);
        if ($resType === null) {
            $resType = $this->resourceType;
        }
        $retVal = EasyRdf::graphToResourceCollection($result, $resType);
        return $retVal;
    }

    /**
     * Sends an ask query for if a match is found for the patterns and returns the boolean result.
     * @param string $query String representation of the patterns.
     * @return boolean
     */
    public function ask($query)
    {
        $query = 'ASK {' . PHP_EOL . $query . PHP_EOL . '}';
        return $this->query($query)->getBoolean();
    }

    /**
     * @return SolrResourceManager
     */
    public function getSolrManager()
    {
        return $this->solrResourceManager;
    }

    /**
     * Execute raw query
     * Retries on timeout, because when jena stays idle for some time, sometimes throws a timeout error.
     *
     * @param string $query
     * @return \EasyRdf\Graph
     * @throws \EasyRdf\Exception
     */
    public function query($query)
    {
        $maxTries = 3;
        $tries = 0;
        $ex = null;
        do {
            try {
                return $this->client->query($query);
            } catch (\EasyRdf\Exception $ex) {
                if (strpos($ex->getMessage(), 'timed out') === false) {
                    throw $ex;
                }
            }
            sleep(30);
            $tries ++;
        } while ($tries < $maxTries && $ex !== null);

        if ($ex !== null) {
            throw $ex;
        }
    }

    /**
     * Performs client->insert. Retry on timeout.
     * @param Graph $data
     * @return Http\Response
     * @throws \EasyRdf\Exception
     */
    protected function insertWithRetry($data)
    {
        $maxTries = 3;
        $tries = 0;
        $ex = null;
        do {
            try {
                return $this->client->insert($data);
            } catch (\EasyRdf\Exception $ex) {
                if (strpos($ex->getMessage(), 'timed out') === false) {
                    throw $ex;
                }
            }
            sleep(1);
            $tries ++;
        } while ($tries < $maxTries && $ex !== null);

        if ($ex !== null) {
            throw $ex;
        }
    }

    /**
     * @param Object $object
     * @return string
     * @throws \EasyRdf\Exception
     */
    protected function valueToTurtle(Object $object)
    {
        $serializer = new NTriple();
        return $serializer->serialize($object);
    }

    /**
     * Makes query (with full sparql patterns) from our search patterns.
     * @param Object[] $simplePatterns Example: [Skos::NOTATION => new Literal('AM002'),]
     * or [0 => ['?subject', Skos::NOTATION, new Literal('AM002'),]
     * @param string $subject
     * @return string
     */
    protected function simplePatternsToQuery($simplePatterns, $subject)
    {
        $query = '';
        if (!empty($simplePatterns)) {
            foreach ($simplePatterns as $predicate => $value) {
                if (!is_integer($predicate)) {
                    $query .= $subject . ' <' . $predicate . '> ' . $this->valueToTurtle($value) . ' .' . PHP_EOL;
                } else {
                    // Build a pattern like
                    // $value[0] <$value[1]> $value[2]
                    $query .= $value[0] instanceof Object ? $this->valueToTurtle($value[0]) : $value[0];
                    $query .= ' <' . $value[1] . '> ';
                    $query .= $value[2] instanceof Object ? $this->valueToTurtle($value[2]) : $value[2];
                    $query .= ' .';
                }
                $query .= PHP_EOL;
            }
        } else {
            // All subjects
            $query .= $subject . ' ?predicate ?object' . PHP_EOL;
        }

        return $query;
    }

    public function fetchUriByCode($code, $typeUri)
    {
        $tenants = $this->fetchSubjectWithPropertyGiven(OpenSkosNamespace::CODE, '"' . $code . '"', $typeUri);
        if (count($tenants) < 1) {
            return null;
        } else {
            return $tenants[0];
        }
    }

    public function fetchRelationUris()
    {
        // all relations, not only concept-concept relations
        $skosrels = Skos::getSkosRelations();
        $userrels = array_values($this->fetchNameUri());
        $result = array_merge($skosrels, $userrels);
        return $result;
    }

    public function getTripleStoreRegisteredCustomRelationTypes()
    {
        $sparqlQuery = 'select ?rel where {?rel <' . RdfNamespace::TYPE . '> <' . Owl::OBJECT_PROPERTY . '> . }';
        $resource = $this->query($sparqlQuery);
        $result = [];
        foreach ($resource as $value) {
            $result[] = $value->rel->getUri();
        }
        return $result;
    }

    public function fetchRowWithRetries($model, $query)
    {
        $tries = 0;
        $maxTries = 3;
        do {
            try {
                return $model->fetchRow($query);
            } catch (\Exception $exception) {
                echo 'retry mysql connect' . PHP_EOL;
                // Reconnect
                $model->getAdapter()->closeConnection();
                $modelClass = get_class($model);
                $model = new $modelClass();
                $tries ++;
            }
        } while ($tries < $maxTries);

        if ($exception) {
            throw $exception;
        }
    }

    // used only for HTML output
    public function getResourceSearchID($resourceReference, $resourceType)
    {
        if ($resourceType === Org::FORMALORG || $resourceType === Dcmi::DATASET) {
            $query = 'SELECT ?code WHERE { <' . $resourceReference . '>  <' . OpenSkosNamespace::CODE . '> ?code .  }';
            $response2 = $this->query($query);
            if ($response2 !== null & count($response2) > 0) {
                return $response2[0]->code->getValue();
            }
        }

        $query = 'SELECT ?uuid WHERE { <' . $resourceReference . '>  <' . OpenSkosNamespace::UUID . '> ?uuid .  }';
        $response1 = $this->query($query);
        if ($response1 !== null & count($response1) > 0) {
            return $response1[0]->uuid->getValue();
        }


        throw new ResourceNotFoundException("The resource with the reference " . $resourceReference . " is not found. ");
    }

    // used only for HTML output
    public function getSetTitle($reference)
    {
        $query = "SELECT ?name WHERE { ?seturi <" . OpenSkosNamespace::CODE . "> '" .
            $reference . "' . ?seturi <" . DcTerms::TITLE . "> ?name . ?seturi <" .
            RdfNamespace::TYPE . "> <" . Dcmi::DATASET . "> . }";
        $response = $this->query($query);
        if ($response !== null & count($response) > 0) {
            return $response[0]->name->getValue();
        }

        $query = 'SELECT ?name WHERE { <' . $reference . '>  <' . DcTerms::TITLE . '> ?name .  <' .
            $reference . '>  <' . RdfNamespace::TYPE . '> <' . Dcmi::DATASET . '> .}';
        $response1 = $this->query($query);
        if ($response1 !== null & count($response1) > 0) {
            return $response1[0]->name->getValue();
        }
    }

    // used only for HTML output
    public function getTenantNameByCode($code)
    {

        $query = "SELECT ?name WHERE { ?tenanturi <" . OpenSkosNamespace::CODE . "> '" .
            $code . "' . ?tenanturi <" . VCard::ORG . "> ?org . ?org <" .
            VCard::ORGNAME . "> ?name . }";
        $response2 = $this->query($query);
        if ($response2 !== null & count($response2) > 0) {
            return $response2[0]->name->getValue();
        };
    }

    // used only for HTML representation to list concepts withit a schema, a set or a skos-collection
    public function listConceptsForCluster($clusterID, $clusterType)
    {
        if ($clusterType === OpenSkosNamespace::SET) { // search on code
            $query = 'SELECT DISTINCT ?uri ?uuid '
                . ' WHERE  { ?uri  <' . RdfNamespace::TYPE . '> <' . Skos::CONCEPT . '> . '
                . ' ?uri  <' . Skos::INSCHEME . '> ?x . '
                . ' ?uri  <' . OpenSkosNamespace::UUID . '> ?uuid . '
                . ' ?x  <' . OpenSkosNamespace::SET . '> <' . $clusterID . '> . }'
            ;
        } else {
            $query = 'SELECT ?uri ?uuid '
                . ' WHERE  {  ?uri  <' . $clusterType . '> <' . $clusterID . '> .'
                . ' ?uri  <' . RdfNamespace::TYPE . '> <' . Skos::CONCEPT . '> . '
                . ' ?uri  <' . OpenSkosNamespace::UUID . '> ?uuid .}'
            ;
        }
        $result = $this->query($query);
        $retval = [];
        foreach ($result as $concept) {
            $retval[$concept->uri->getUri()] = trim($concept->uuid->getValue());
        }
        return $retval;
    }

    // output is a list of related concepts, used in both managers: relation and concept.
    public function fetchRelatedConcepts($uri, $relationType, $isTarget, $conceptScheme = null)
    {

        if ($isTarget) {
            $startQuery = 'DESCRIBE ?subject {SELECT DISTINCT ?subject   WHERE { ?subject <' .
                $relationType . '> <' . $uri . '> . ';
            if ($conceptScheme == null) {
                $endQuery = '} }';
            } else {
                $endQuery = '  <' . $uri . '> <' . Skos::INSCHEME . '> <' . $conceptScheme . '>. } }';
            }
        } else {
            $startQuery = 'DESCRIBE ?object {SELECT DISTINCT ?object   WHERE { <' .
                $uri . '> <' . $relationType . '> ?object . ';
            if ($conceptScheme == null) {
                $endQuery = '} }';
            } else {
                $endQuery = '  ?object <' . Skos::INSCHEME . '> <' . $conceptScheme . '>. } }';
            }
        };

        $relatedConcepts = $this->fetchQuery($startQuery . $endQuery, Concept::TYPE);
        return $relatedConcepts;
    }

    public function setUriWithEmptinessCheck(&$resource, $property, $val)
    {
        if ($val !== null && $val !== '') {
            $resource->setProperty($property, new \OpenSkos2\Rdf\Uri($val));
        };
    }

    public function setLiteralWithEmptinessCheck(&$resource, $property, $val)
    {
        if ($val !== null && $val !== '') {
            $resource->setProperty($property, new \OpenSkos2\Rdf\Literal($val));
        };
    }

    public function setBooleanLiteralWithEmptinessCheck(&$resource, $property, $val)
    {
        if ($val === null) {
            $resource->setProperty(
                $property, new \OpenSkos2\Rdf\Literal('false', null, \OpenSkos2\Rdf\Literal::TYPE_BOOL)
            );
            return;
        } else {
            if (strtolower($val) === 'y' || strtolower($val) === "yes") {
                $resource->setProperty(
                    $property, new \OpenSkos2\Rdf\Literal('true', null, \OpenSkos2\Rdf\Literal::TYPE_BOOL)
                );
                return;
            }
            if (strtolower($val) === 'n' || strtolower($val) === "no") {
                $resource->setProperty(
                    $property, new \OpenSkos2\Rdf\Literal('false', null, \OpenSkos2\Rdf\Literal::TYPE_BOOL)
                );
                return;
            }
        }
        $resource->setProperty($property, new \OpenSkos2\Rdf\Literal($val, null, \OpenSkos2\Rdf\Literal::TYPE_BOOL));
    }

    // Id is either an URI or uuid, or, for user concevinece, code for tenants and sets
    public function findResourceById($id, $resourceType)
    {
        if ($id !== null && isset($id)) {
            if (substr($id, 0, 7) === "http://" || substr($id, 0, 8) === "https://") {
                $resource = $this->fetchByUri($id, $resourceType);
            } else {
                try {
                    $resource = $this->fetchByUuid($id, $resourceType);
                } catch (\Exception $ex) {
                    if ($resourceType == Org::FORMALORG || $resourceType == Dcmi::DATASET) {
                        try {
                            $resource = $this->fetchByCode($id, $resourceType);
                        } catch (\Exception $ex2) {
                            throw new ResourceNotFoundException(
                            'The resource of type ' . $resourceType .
                            ' with the id/uri/code ' . $id . ' cannot be found (detailed reasons : ' .
                            $ex->getMessage() . ' AND   ' . $ex2->getMessage() . ')');
                        }
                    } else {
                        throw new ResourceNotFoundException(
                        'The resource of type ' . $resourceType .
                        ' with the id/uri ' . $id . ' cannot be found (detailed reasons : ' .
                        $ex->getMessage() . ')');
                    }
                }
            }

            return $resource;
        } else {
            throw new \Exception('No Id (URI or UUID, or Code) is given');
        }
    }

    public function fetchTenantNameUri()
    {
        $query = 'SELECT ?uri ?name WHERE { ?uri  <' . VCard::ORG . '> ?org . ?org <' . VCard::ORGNAME . '> ?name . }';
        $response = $this->query($query);
        $result = $this->makeNameUriMap($response);
        return $result;
    }

    public function augmentResourceWithTenant($resource)
    {
        if ($resource !== null) {
            $rdfTypes = $resource->getProperty(RdfNamespace::TYPE);
            $rdfType = $rdfTypes[0]->getUri();
            if ($rdfType !== \OpenSkos2\ConceptScheme::TYPE &&
                $rdfType !== \OpenSkos2\SkosCollection::TYPE &&
                $rdfType !== \OpenSkos2\Set::TYPE) {
                throw new \Exception(
                "The method augmentResourceWithTenant can be used"
                . "only for concept schemata, skos collections and sets. "
                );
            }
            $tenants = $resource->getProperty(OpenSkosNamespace::TENANT);
            if (count($tenants) < 1) {
                $tenantUri = $this->fetchTenantUriViaSet($resource);
                if ($tenantUri !== null) {
                    $resource->setProperty(OpenSkosNamespace::TENANT, $tenantUri);
                }
            }
        }
        return $resource;
    }

    private function fetchTenantUriViaSet($resource)
    {
        $rdfTypes = $resource->getProperty(RdfNamespace::TYPE);
        $rdfType = $rdfTypes[0]->getUri();

        if ($rdfType !== \OpenSkos2\ConceptScheme::TYPE && $rdfType !== \OpenSkos2\SkosCollection::TYPE) {
            throw new \Exception(
            "The method fetchTenantUriViaSet can be used only"
            . "for concept chemata and skos collections. "
            );
        }
        if ($resource !== null) {
            $setUris = $resource->getProperty(OpenSkosNamespace::SET);
            if (count($setUris) > 0) {
                $set = $this->fetchByUri($setUris[0]->getUri(), \OpenSkos2\Set::TYPE);
                $tenantUris = $set->getProperty(DcTerms::PUBLISHER);
                if (count($tenantUris) > 0) {
                    return $tenantUris[0];
                }
            }
        }
        return null;
    }

    public function fetchConceptSpec($concept)
    {
        $uri = $concept->getUri();
        $query = 'SELECT DISTINCT ?tenanturi ?tenantname ?tenantcode ?seturi ?setcode ?settitle ?creatorname WHERE { '
            . '<' . $uri . '> <' . Skos::INSCHEME . '> ?schemauri . '
            . '?schemauri <' . OpenSkosNamespace::SET . '> ?seturi . '
            . '?seturi <' . DcTerms::PUBLISHER . '> ?tenanturi .'
            . '?seturi <' . OpenSkosNamespace::CODE . '> ?setcode .'
            . '?seturi <' . DcTerms::TITLE . '> ?settitle .'
            . '?tenanturi  <' . VCard::ORG . '> ?org . '
            . '?org <' . VCard::ORGNAME . '> ?tenantname . '
            . '?tenanturi <' . OpenSkosNamespace::CODE . '> ?tenantcode .'
            . '<' . $uri . '> <' . DcTerms::CREATOR . '> ?creatoruri . '
            . '?creatoruri <' . Foaf::NAME . '> ?creatorname . '
            . '}';

        $response = $this->query($query);
        $retVal = [];
        foreach ($response as $descr) {
            $spec = [];
            $spec['tenanturi'] = $descr->tenanturi->getUri();
            $spec['tenantname'] = $descr->tenantname->getValue();
            $spec['tenantcode'] = $descr->tenantcode->getValue();
            $spec['seturi'] = $descr->seturi->getUri();
            $spec['setcode'] = $descr->setcode->getValue();
            $spec['settitle'] = $descr->settitle->getValue();
            $spec['creatorname'] = $descr->creatorname->getValue();
            $retVal[] = $spec;
        }
        if (count($retVal) > 0) {
            return $retVal;
        }
        
        // attempt to fetch tenants ans sets with unknown creator, used e.g. when migrating
        $queryNoCreator = 'SELECT DISTINCT ?tenanturi ?tenantname ?tenantcode ?seturi ?setcode ?settitle ?creatorname WHERE { '
            . '<' . $uri . '> <' . Skos::INSCHEME . '> ?schemauri . '
            . '?schemauri <' . OpenSkosNamespace::SET . '> ?seturi . '
            . '?seturi <' . DcTerms::PUBLISHER . '> ?tenanturi .'
            . '?seturi <' . OpenSkosNamespace::CODE . '> ?setcode .'
            . '?seturi <' . DcTerms::TITLE . '> ?settitle .'
            . '?tenanturi  <' . VCard::ORG . '> ?org . '
            . '?org <' . VCard::ORGNAME . '> ?tenantname . '
            . '?tenanturi <' . OpenSkosNamespace::CODE . '> ?tenantcode .'
            . '}';
        
        $responseNoCreator = $this->query($queryNoCreator);
        foreach ($responseNoCreator as $descr) {
            $spec = [];
            $spec['tenanturi'] = $descr->tenanturi->getUri();
            $spec['tenantname'] = $descr->tenantname->getValue();
            $spec['tenantcode'] = $descr->tenantcode->getValue();
            $spec['seturi'] = $descr->seturi->getUri();
            $spec['setcode'] = $descr->setcode->getValue();
            $spec['settitle'] = $descr->settitle->getValue();
            $spec['creatorname'] = 'Unknown';
            $retVal[] = $spec;
        } 
        
        return $retVal;
    }

    public function fetchTenantSpecForConceptToAdd($concept)
    {
        $properties = [Skos::INSCHEME, OpenSkosNamespace::INSKOSCOLLECTION];
        $retVal = [];
        for ($i = 0; $i < 2; $i++) {
            $refs = $concept->getProperty($properties[$i]);

            foreach ($refs as $ref) {
                $query = 'SELECT DISTINCT ?tenanturi ?tenantname ?tenantcode ?seturi ?setcode ?settitle WHERE { '
                    . '<' . $ref->getUri() . '> <' . OpenSkosNamespace::SET . '> ?seturi . ?seturi <' .
                    DcTerms::PUBLISHER . '> ?tenanturi .'
                    . '?seturi <' . OpenSkosNamespace::CODE . '> ?setcode .'
                    . '?seturi <' . DcTerms::TITLE . '> ?settitle .'
                    . '?tenanturi  <' . VCard::ORG . '> ?org . ?org <' .
                    VCard::ORGNAME . '> ?tenantname . ?tenanturi <' .
                    OpenSkosNamespace::CODE . '> ?tenantcode .}';

                $response = $this->query($query);
                foreach ($response as $descr) {
                    $spec = [];
                    $spec['tenanturi'] = $descr->tenanturi->getUri();
                    $spec['tenantname'] = $descr->tenantname->getValue();
                    $spec['tenantcode'] = $descr->tenantcode->getValue();
                    $spec['seturi'] = $descr->seturi->getUri();
                    $spec['setcode'] = $descr->setcode->getValue();
                    $spec['settitle'] = $descr->settitle->getValue();
                    $retVal[] = $spec;
                }
            }
        }
        return $retVal;
    }

    public function getCustomRelationTypes()
    {
        if ($this->init["custom.default_relationtypes"]) {
            return [];
        } else {
            return $this->customRelationTypes->getRelationTypes();
        }
    }

    public function getCustomInverses()
    {
        if ($this->init["custom.default_relationtypes"]) {
            return [];
        } else {
            return $this->customRelationTypes->getInverses();
        }
    }

    public function getCustomTransitives()
    {
        if ($this->init["default_relationtypes"]) {
            return [];
        } else {
            return $this->customRelationTypes->getTransitives();
        }
    }

    public function setCustomRelationTypes($relationtypes)
    {
        if ($this->init["custom.default_relationtypes"]) {
            return;
        } else {
            $this->customRelationTypes->setRelationTypes($relationtypes);
        }
    }

    public function setCustomInverses($inverses)
    {
        if ($this->init["custom.default_relationtypes"]) {
            return;
        } else {
            $this->customRelationTypes->setInverses($inverses);
        }
    }

    public function setCustomTransitives($transitives)
    {
        if ($this->init["custom.default_relationtypes"]) {
            return;
        } else {
            $this->customRelationTypes->setTransitives($transitives);
        }
    }

    public function fetchConceptConceptRelationsNameUri()
    {
        $uris = Skos::getSkosConceptConceptRelations();
        $skosrels = [];
        foreach ($uris as $uri) {
            $border = strrpos($uri, "#");
            $name = 'skos:' . substr($uri, $border + 1);
            $skosrels[$name] = $uri;
        }
        $userrels = $this->getCustomRelationTypes();
        $result = array_merge($skosrels, $userrels);
        return $result;
    }

    // a relation is invalid if it (possibly with its inverse) creates transitive
    // link of a concept or related concept to itself
    public function relationTripleCreatesCycle($conceptUri, $relatedConceptUri, $relationUri)
    {
        $closure = $this->getClosure($relatedConceptUri, $relationUri);
        $transitive = ($conceptUri === $relatedConceptUri || in_array($conceptUri, $closure));
        if ($transitive) {
            throw new \Exception(
            "The triple ($conceptUri, $relatedConceptUri, $relationUri) creates transitive link of the source to itself, '
            . 'possibly via inverse relation.");
        }
        // overkill??
        $inverses = array_merge(Skos::getInverseRelationsMap(), $this->customRelationTypes->getInverses());
        if (array_key_exists($relationUri, $inverses)) {
            $inverseRelUri = $inverses[$relationUri];
            $inverseClosure = $this->getClosure($conceptUri, $inverseRelUri);
            $transitiveInverse = ($relatedConceptUri === $conceptUri || in_array($relatedConceptUri, $inverseClosure));
            if ($transitiveInverse) {
                throw new \Exception(
                "The triple ($conceptUri, $relatedConceptUri, $relationUri) creates inverse transitive link of the target to itself");
            }
        }
    }

    public function relationTripleIsDuplicated($conceptUri, $relatedConceptUri, $relationUri)
    {
        $count = $this->countTriples(
            '<' . $conceptUri . '>', '<' . $relationUri . '>', '<' . $relatedConceptUri . '>'
        );
        if ($count > 0) {
            throw new \Exception(
            "There is an attempt to duplicate a relation: ($conceptUri, $relationUri, $relatedConceptUri)"
            );
        }

        $trans = $this->customRelationTypes->getTransitives();
        if (!isset($trans[$relationUri]) || $trans[$relationUri] == null) {
            $closure = $this->getClosure($conceptUri, $relationUri);
            if (in_array($relatedConceptUri, $closure)) {
                throw new \Exception(
                "There is an attempt to duplicate a relation: ($conceptUri, $relationUri, $relatedConceptUri) which is in the transitive closure."
                );
            }
        }
        return false;
    }

    public function isRelationURIValid($relUri, $customRelUris = null, $registeredRelationUris = null, $allRelationUris = null)
    {
        if ($customRelUris == null) {
            $customRelUris = array_values($this->getCustomRelationTypes());
        }
        if ($registeredRelationUris == null) {
            $registeredRelationUris = array_values($this->getTripleStoreRegisteredCustomRelationTypes());
        }
        if ($allRelationUris == null) {
            $allRelationUris = array_values($this->fetchConceptConceptRelationsNameUri());
        }
        if (in_array($relUri, $allRelationUris)) {
            if (in_array($relUri, $customRelUris)) {
                if (!in_array($relUri, $registeredRelationUris)) {
                    throw new \Exception(
                    'The relation  ' . $relUri .
                    '  is not registered in the triple store. ');
                }
            }
        } else {
            throw new \Exception(
            'The relation type ' . $relUri . '  is neither a skos concept-concept '
            . 'relation type nor a custom relation type. ');
        }
    }

    // all concepts from transitive closure for $conceptsUri;
    private function getClosure($conceptUri, $relationUri)
    {
        $query = 'select ?trans where {<' . $conceptUri . '>  <' . $relationUri . '>+ ' . '  ?trans . }';
        $response = $this->query($query);
        $retVal = array();
        $i = 0;
        foreach ($response as $key => $value) {
            $retVal[$i] = $value->trans->getUri();
            $i++;
        }
        return $retVal;
    }

}
