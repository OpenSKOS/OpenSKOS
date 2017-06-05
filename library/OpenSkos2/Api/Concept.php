<?php

/*
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

namespace OpenSkos2\Api;

use OpenSkos2\Api\Exception\ApiException;
use OpenSkos2\Api\Exception\InvalidArgumentException;
use OpenSkos2\Api\Response\ResultSet\JsonpResponse;
use OpenSkos2\Api\Response\ResultSet\JsonResponse;
use OpenSkos2\Api\Response\ResultSet\RdfResponse;
use OpenSkos2\ConceptManager;
use OpenSkos2\Tenant;
use OpenSkos2\Set;
use OpenSkos2\PersonManager;
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Namespaces\Dc;
use OpenSkos2\Search\Autocomplete;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ServerRequestInterface as PsrServerRequestInterface;
use Zend\Diactoros\Stream;
use Zend\Diactoros\Response;

// Meerens: 
// -- all concrete resource api classes extends AbstractTripleStoreResource . 
// This abtsract class contains generic methods for get, create, update and delete for any 
// kind of resource. 
// -- Here in the Concept class only concept-secific "autocomplete" and "findConcepts" are implemented, 
// the other methods can be found in the parent class.
// -- ApiResponseTrait is not used any more.
// -- Maximal time limit is changed at the begin of "fincConceptMethod" (by the constant set in the config),
// and set back before the method return.
// -- Maximal rows are set via the config's constant as well, 
// not via $this->limit as it has been implemented by picturae
// -- 'collection' is replaced by 'set'
// -- added 'label' to options in findConcepts otherwise $options['label'] 
// in autocomplete->search is useless (also see my e-mail 2/02 question
// about "where translation prefLabel to t_prefLabel or a_prefLabel happens")
// 
//-- added new parameter 'wholeword' to handle switch between whole word 
// search (prefix t_) and the part-of-word search (prefix a_)
// // -- added 'properties' to options otherwise $options['properties'] in autocomplete->search is useless

class Concept extends AbstractTripleStoreResource
{

    use \OpenSkos2\Api\Response\ApiResponseTrait;

    /**
     * Search autocomplete
     *
     * @var Autocomplete
     */
    protected $searchAutocomplete;

    /**
     *
     * @param ConceptManager $manager
     * @param Autocomplete $searchAutocomplete
     * @param PersonManager $personManager
     */
    public function __construct(
    ConceptManager $manager, Autocomplete $searchAutocomplete, PersonManager $personManager 
    )
    {
        $this->manager = $manager;
        $this->authorisation = new \OpenSkos2\Authorisation($manager);
        $this->deletion = new \OpenSkos2\Deletion($manager);
        $this->personManager = $personManager;
        $this->init = parse_ini_file(__DIR__ . '/../../../application/configs/application.ini');
        $this->searchAutocomplete = $searchAutocomplete;
    }

    /**
     * Map the following requests
     *
     * /api/find-concepts?q=Kim%20Holland
     * /api/find-concepts?&fl=prefLabel,scopeNote&format=json&q=inScheme:"http://uri"
     * /api/find-concepts?format=json&fl=uuid,uri,prefLabel,class,dc_title&id=http://data.beeldengeluid.nl/gtaa/27140
     * /api/concept/82c2614c-3859-ed11-4e55-e993c06fd9fe.rdf
     *
     * @param ServerRequestInterface $request
     * @param string $context
     * @return ResponseInterface
     * @throws InvalidArgumentException
     */
    public function findConcepts(PsrServerRequestInterface $request, $context)
    {
        set_time_limit($this->init["custom.maximal_time_limit"]);

        $params = $request->getQueryParams();

// offset
        $start = 0;
        if (!empty($params['start'])) {
            $start = (int) $params['start'];
        }

// limit
        $limit = $this->init["custom.maximal_rows"];
        if (isset($params['rows']) && $params['rows'] < $limit) {
            $limit = (int) $params['rows'];
        }


        $options = [
            'start' => $start,
            'rows' => $limit,
            'status' => [\OpenSkos2\Concept::STATUS_CANDIDATE, \OpenSkos2\Concept::STATUS_APPROVED],
        ];

        // tenants

        $tenantCodes = [];
        if (isset($params['tenant'])) {
            $tenantCodes = explode(' ', trim($params['tenant']));
        } else { // synomym parameter
            if (isset($params['tenantUri'])) {
                $options['tenants'] = explode(' ', trim($params['tenantUri']));
            }
        }

        if (count($tenantCodes) > 0) {
            foreach ($tenantCodes as $tenantcode) {
                $tenantUri = $this->manager->fetchUriByCode($tenantcode, Tenant::TYPE);
                if ($tenantUri === null) {
                    throw new ApiException(
                    'The tenant referred by code ' . $tenantcode .
                    ' does not exist in the triple store. ', 400
                    );
                };
                $options['tenants'][] = $tenantUri;
            }
        }


        // sets (former collections)

        if (isset($params['setUri'])) {
            $options['set'] = explode(' ', trim($params['setUri']));
        } else {
            $setcodes = null;
            if (isset($params['set'])) {
                $setcodes = explode(' ', trim($params['set']));
            } else {
                if (isset($params['collection'])) {
                    $setcodes = explode(' ', trim($params['collection']));
                }
            }
            if ($setcodes !== null) {
                $options['sets'] = array();
                foreach ($setcodes as $setcode) {
                    $setUri = $this->manager->fetchUriByCode($setcode, Set::TYPE);
                    if ($setUri === null) {
                        throw new ApiException(
                        "The set (former known as collection) referred by code  "
                        . "$setcode does not exist in the triple store.", 400
                        );
                    } else {
                        $options['sets'][] = $setUri;
                    }
                }
            }
        }

        
        // concept scheme 
        if (isset($params['conceptScheme'])) {
            $options['scheme'] = explode(' ', trim($params['conceptScheme']));
        } else {
            if (isset($params['scheme'])) {
                $options['scheme'] = explode(' ', trim($params['scheme']));
            }
        }

        // skos collection
        if (isset($params['skosCollection'])) {
            $options['skosCollection'] = explode(' ', trim($params['skosCollection']));
        }


        // search query
        if (isset($params['q'])) {
            $options['searchText'] = $params['q'];
        }

        if (isset($params['label'])) {
            $options['label'] = explode(' ', trim($params['label']));
        }

        if (isset($params['properties'])) {
            $options['properties'] = explode(' ', trim($params['properties']));
        }

        if (isset($params['sorts'])) {
            $sortmap = $this->prepareSortsForSolr($params['sorts']);
            $options['sorts'] = $sortmap;
        }

        if (isset($params['status'])) {
            $options['status'] = explode(' ', trim($params['status']));
        }

        $options['wholeword'] = false;
        if (isset($params['wholeword'])) {
            if ($params['wholeword'] === 'true') {
                $options['wholeword'] = true;
            }
        }

        $concepts = $this->searchAutocomplete->search($options, $total);

        foreach ($concepts as $concept) {
            $specs = $this->manager->fetchConceptSpec($concept);
                foreach ($specs as $spec) {
                    $concept->addProperty(OpenSkos::SET, new \OpenSkos2\Rdf\Literal($spec['setcode']));
                    $concept->addProperty(OpenSkos::TENANT, new \OpenSkos2\Rdf\Literal($spec['tenantcode']));
                    $concept->addProperty(Dc::CREATOR, new \OpenSkos2\Rdf\Literal($spec['creatorname']));
                }          
        }
        
        $result = new ResourceResultSet($concepts, $total, $start, $limit);

        if (isset($params['fl'])) {
            $propertiesList = $this->fieldsListToProperties($params['fl']);
        } else {
            $propertiesList = [];
        }
        switch ($context) {
            case 'json':
                $response = (new JsonResponse($result, $propertiesList))->getResponse();
                break;
            case 'jsonp':
                $response = (new JsonpResponse($result, $params['callback'], $propertiesList))->getResponse();
                break;
            case 'rdf':
                $response = (new RdfResponse($result, $propertiesList))->getResponse();
                break;
            default:
                throw new InvalidArgumentException('Invalid context: ' . $context);
        }
        set_time_limit($this->init["custom.normal_time_limit"]);
        return $response;
    }

    private function prepareSortsForSolr($sortstring)
    {
        $sortlist = explode(" ", $sortstring);
        $l = count($sortlist);
        $sortmap = [];
        $i = 0;
        while ($i < $l - 1) { // the last element will be worked-on after the loop is finished
            $j = $i;
            $i++;
            $sortfield = $this->prepareSortFieldForSolr($sortlist[$j]);
            $sortorder = 'asc';
            if ($sortlist[$i] === "asc" || $sortlist[$i] === 'desc') {
                $sortorder = $sortlist[$i];
                $i++;
            }
            $sortmap[$sortfield] = $sortorder;
        };
        if ($sortlist[$l - 1] !== 'asc' && $sortlist[$l - 1] !== 'desc') {
// field name is the last and no order after it
            $sortfield = $this->prepareSortFieldForSolr($sortlist[$l - 1]); // Fix "@nl" to "_nl"
            $sortmap[$sortfield] = 'asc';
        };
        return $sortmap;
    }

    private function prepareSortFieldForSolr($term)
    {
        // translate field name  to am internal sort-field name
        if (substr($term, 0, 5) === "sort_" || substr($term, strlen($term) - 3, 1) === "_") {
// is already an internal presentation ready for solr, starts with sort_* or *_langcode
            return $term;
        }
        if ($this->isDateField($term)) {
            return "sort_d_" . $term;
        } else {
            if (strpos($term, "@") !== false) {
                return str_replace("@", "_", $term);
            } else {
                return "sort_s_" . $term;
            }
        }
    }

    private function isDateField($term)
    {
        return ($term === "dateAccepted" || $term === "dateSubmitted" || $term === "modified");
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return Response
     */
    public function addRelationTriple(PsrServerRequestInterface $request)
    {
        $params = $this->getParams($request);
        $tenant = $this->getTenantFromParams($params);
        $user = $this->getUserFromParams($params)->getFoafPerson();
        $set = $this->getSet($params, $tenant);
        try {
            $body = $this->preEditChecksRels($request, $user, $tenant, $set, false);
            $this->manager->addRelationTriple($body['concept'], $body['type'], $body['related']);
        } catch (ApiException $exc) {
            return $this->getErrorResponse($exc->getCode(), $exc->getMessage());
        }

        $stream = new Stream('php://memory', 'wb+');
        $stream->write('Relations added');
        $response = (new Response())
            ->withBody($stream);
        return $response;
    }

    /**
     * @param PsrServerRequestInterface $request
     * @return Response
     */
    public function deleteRelationTriple(PsrServerRequestInterface $request)
    {
        $params = $this->getParams($request);
        $tenant = $this->getTenantFromParams($params);
        $user = $this->getUserFromParams($params)->getFoafPerson();
        $set = $this->getSet($params, $tenant);
        try {
            $body = $this->preEditChecksRels($request, $user, $tenant, $set, true);
            $this->manager->deleteRelationTriple($body['concept'], $body['type'], $body['related']);
        } catch (Exception $e) {
            return $this->getErrorResponseFromException($e);
        }

        $stream = new Stream('php://memory', 'wb+');
        $stream->write('Relation deleted');
        $response = (new Response())
            ->withBody($stream);
        return $response;
    }

    private function preEditChecksRels(PsrServerRequestInterface $request, $user, $tenant, $set, $toBeDeleted)
    {

        $body = $request->getParsedBody();
        if (!isset($body['concept'])) {
            throw new ApiException('Missing concept', 400);
        }
        if (!isset($body['related'])) {
            throw new ApiException('Missing related', 400);
        }
        if (!isset($body['type'])) {
            throw new ApiException('Missing type', 400);
        }

        $exists1 = $this->manager->resourceExists($body['concept'], Skos::CONCEPT);
        if (!$exists1) {
            throw new ApiException('The concept referred by the uri ' . $body['concept'] . ' does not exist.', 404);
        }

        $exists2 = $this->manager->resourceExists($body['related'], Skos::CONCEPT);
        if (!$exists2) {
            throw new ApiException('The concept referred by the uri ' . $body['related'] . ' does not exist.', 404);
        }

        $validURI = $this->manager->isRelationURIValid($body['type']); // throws an exception otherwise

        if (!$toBeDeleted) {
            $this->manager->relationTripleIsDuplicated($body['concept'], $body['related'], $body['type']);
            $this->manager->relationTripleCreatesCycle($body['concept'], $body['related'], $body['type']);
        }

        $concept = $this->manager->fetchByUri($body['concept'], $this->manager->getResourceType());
        $this->authorisation->resourceEditAllowed(
            $user, $tenant, $set, $concept
        ); // throws an exception if not allowed
        $relatedConcept = $this->manager->fetchByUri($body['related'], $this->manager->getResourceType());
        $this->authorisation->resourceEditAllowed(
            $user, $tenant, $set, $relatedConcept
        ); // throws an exception if not allowed

        return $body;
    }

}
