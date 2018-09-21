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
use OpenSkos2\Namespaces\VCard;
use OpenSkos2\Namespaces\Rdf;
use OpenSkos2\Rdf\ResourceManager;
use OpenSkos2\Namespaces\OpenSkos as OpenSkosNamespace;
use OpenSkos2\Namespaces\Org as Org;
use OpenSkos2\Tenant;
use OpenSkos2\Set;

class TenantManager extends ResourceManager
{

    protected $resourceType = Tenant::TYPE;

    /*
     * @param string $code Tenant Code
     * @return array list of uuids of sets(collections) on the tenant
     */
    public function fetchSetUrisForTenant($code)
    {
        $query = <<<SELECT_SETS
prefix dcterms: <http://purl.org/dc/terms/>
prefix rdf-syntax: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
prefix SKOS: <http://www.w3.org/2004/02/skos/core#>
prefix OPENSKOS: <http://openskos.org/xmlns#>

SELECT ?setUri
WHERE  { 
  ?tenanturi  OPENSKOS:code "%s" 
  .?setUri  dcterms:publisher ?tenanturi 
  .?setUri rdf-syntax:type OPENSKOS:set
}
SELECT_SETS;
        $query = sprintf($query, $code);
        $setCodes = array();
        $response = $this->query($query);
        if ($response !== null) {
            foreach ($response as $triple) {
                $setCodes[] = $triple->setUri->getUri();
            }
        }
        return $setCodes;
    }
    // used only for HTML representation
    public function fetchSetsForTenant($code)
    {
        $query = 'SELECT ?seturi ?p ?o 
        WHERE  { ?tenanturi  <' . OpenSkos::CODE . "> '" . $code . "' ."
            . ' ?seturi  <' . DcTerms::PUBLISHER . '> ?tenanturi .'
            . ' ?seturi  <' . Rdf::TYPE . '> <'.Set::TYPE.'> .'
            . ' ?seturi  ?p ?o .}';
        $response = $this->query($query);
        if ($response !== null) {
            if (count($response) > 0) {
                $retVal = $this->arrangeTripleStoreSets($response);
                return $retVal;
            }
        }

        return [];
    }

    public function fetchSetsForTenantUri($tenantUri)
    {
        $query = "DESCRIBE ?subject  {SELECT DISTINCT ?subject WHERE { "
            . "?subject <" . DcTerms::PUBLISHER . "> <$tenantUri>. "
            . "?subject <" . Rdf::TYPE . "> <".\OpenSkos2\Set::TYPE.">.} }";
        $response = $this->query($query);
        return $response;
    }

    // used only for html output
    private function arrangeTripleStoreSets($response)
    {
        $retVal = [];
        foreach ($response as $triple) {
            $seturi = $triple->seturi->getUri();
            if (!array_key_exists($seturi, $retVal)) {
                $retVal[$seturi] = [];
            };
            switch ($triple->p) {
                case DcTerms::TITLE:
                    $retVal[$seturi]['dcterms_title'] = $triple->o->getValue();
                    continue;
                case DcTerms::DESCRIPTION:
                    $retVal[$seturi]['dcterms_description'] = $triple->o->getValue();
                    continue;
                case OpenSkos::WEBPAGE:
                    $retVal[$seturi]['openskos_webpage'] = $triple->o->getUri();
                    continue;
                case OpenSkos::CODE:
                    $retVal[$seturi]['openskos_code'] = $triple->o->getValue();
                    continue;
                case OpenSkos::UUID:
                    $retVal[$seturi]['openskos_uuid'] = $triple->o->getValue();
                    continue;
                default:
                    continue;
            }
        }
        return $retVal;
    }

    public function fetchNameUri()  // orgname -> uri for tenants
    {
        $query = 'SELECT ?uri ?name WHERE { ?uri  <' . VCard::ORG . '> ?org . '
            . '?org <' . VCard::ORGNAME . '> ?name . }';
        $response = $this->query($query);
        $result = $this->makeNameUriMap($response);
        return $result;
    }

    public function fetchNameSearchID() // orgname -> code for tenants
    {
        $query = 'SELECT ?name ?searchid WHERE { ?uri  <' . VCard::ORG . '> ?org . '
        . '?org <' . VCard::ORGNAME . '> ?name .'
        . ' ?uri  <' . OpenSkos::CODE . '> ?searchid }';
        $response = $this->query($query);
        $result = $this->makeNameSearchIDMap($response);
        return $result;
    }

    public function getTenantUuidFromCode($code)
    {
        $query = <<<SELECT_URI
SELECT ?uuid WHERE { 
  ?uri  <%s> <%s>.
  ?uri  <%s> "%s".
  ?uri  <%s> ?uuid
}
SELECT_URI;
        $query = sprintf($query, Rdf::TYPE, Org::FORMALORG, OpenSkosNamespace::CODE, $code, OpenSkos::UUID);

        $response = $this->query($query);
        if (count($response) > 1) {
            throw new \Exception("Something went very wrong: there more than 1 institution with the code $code");
        }
        if (count($response) < 1) {
            throw new \Exception("the institution with the code $code is not found");
        }
        return $response[0]->uuid->getValue();
    }

    public function fetchTenantFromCode($code)
    {
        $query = <<<SELECT_URI
DESCRIBE ?uri {
    SELECT ?uri WHERE { 
      ?uri  <%s> <%s>.
      ?uri  <%s> "%s".
    }
}
SELECT_URI;
        $query = sprintf($query, Rdf::TYPE, Org::FORMALORG, OpenSkosNamespace::CODE, $code);

        $response = $this->fetchQuery($query);

        if (count($response) > 1) {
            throw new \Exception("Something went very wrong: there more than 1 institution with the code $code");
        }
        if (count($response) < 1) {
            throw new \Exception("the institution with the code $code is not found");
        }
        return $response[0];
    }


    /**
     * @param Uri $resource
     */
    public function delete(\OpenSkos2\Rdf\Uri $resource)
    {
        $this->client->update("DELETE WHERE {<{$resource->getUri()}> <".VCard::ORG."> ?object . "
            . "?object ?predicate2 ?object2 .}");
        $this->client->update("DELETE WHERE {<{$resource->getUri()}> <".VCard::ADR."> ?object . "
            . "?object ?predicate2 ?object2 .}");
        $this->client->update("DELETE WHERE {<{$resource->getUri()}> ?predicate ?object}");
    }


    /**
     * Gets the RDF object for the logged in tenant
     * @greturn logged in tenant or null
     */
    public static function getLoggedInTenant()
    {
        $tenant = null;

        $diContainer =  \Zend_Controller_Front::getInstance()->getDispatcher()->getContainer();
        $tenantManager = $diContainer->get('OpenSkos2\TenantManager');


        $user = \OpenSKOS_Db_Table_Users::requireFromIdentity();
        if ($user) {
            $tenantUuid = $tenantManager->getTenantUuidFromCode($user->tenant);
            $tenant = $tenantManager->fetchByUuid($tenantUuid);
        }
        return $tenant;
    }

    public function getAllTenants()
    {
        $query = <<<SELECT_TENANTS
SELECT ?uri ?code ?uuid WHERE { 
  ?uri  <%s> <%s>.
  ?uri  <%s> ?code.
  ?uri  <%s> ?uuid
}
SELECT_TENANTS;
        $query = sprintf($query, Rdf::TYPE, Org::FORMALORG, OpenSkosNamespace::CODE, OpenSkos::UUID);

        $response = $this->query($query);

        $results = array();

        foreach ($response as $tenant) {
            $results[$tenant->uri->getUri()] =
                array(
                    'code' => $tenant->code->getValue(),
                    'uuid' => $tenant->uuid->getValue(),
                );
        }
        return $results;
    }
}
