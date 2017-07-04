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
use OpenSkos2\Set;

class SetManager extends ResourceManager
{

    /**
     * What is the basic resource for this manager.
     * @var string NULL means any resource.
     */
    protected $resourceType = Set::TYPE;

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

   
    
   
    public function fetchNameSearchID() // title -> code for sets
    {
        $query = 'SELECT ?name ?searchid WHERE { ?uri  <' . DcTerms::TITLE . '> ?name . '
        . '?uri  <' . OpenSkos::CODE . '> ?searchid . '
        . '?uri  <' . Rdf::TYPE . '> <'.Set::TYPE.'> .}';
        $response = $this->query($query);
        $result = $this->makeNameSearchIDMap($response);
        return $result;
    }
    
    
    public function listConceptsForSet($code)
    {
        $query = "SELECT ?name ?searchid WHERE {?seturi  <" . OpenSkos::CODE . "> '$code' ."
        . " ?seturi  <" . Rdf::TYPE . "> <".Set::TYPE."> . "
        . "?concepturi  <" . OpenSkos::CODE . "> '$code' . "
        . "?concepturi  <" . Skos::PREFLABEL . "> ?name . "
        . "?concepturi  <" . OpenSkos::UUID . "> ?serachid .}";
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
}
