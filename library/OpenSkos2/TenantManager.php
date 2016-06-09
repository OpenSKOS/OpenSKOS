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
use OpenSkos2\Namespaces\vCard;
use OpenSkos2\Rdf\ResourceManager;
use OpenSkos2\Rdf\Uri;
use OpenSkos2\Rdf\Resource;
use OpenSkos2\Tenant;
use OpenSKOS_Db_Table_Tenants;
use OpenSkos2\Api\Exception\ApiException;

class TenantManager extends ResourceManager
{
  
    protected $resourceType = Tenant::TYPE;
   
    public function fetchNameUri() {
        $query = 'SELECT ?uri ?name WHERE { ?uri  <' . vCard::ORG . '> ?org . ?org <' . vCard::ORGNAME . '> ?name . }';
        $response = $this->query($query);
        $result = $this->makeNameUriMap($response);
        return $result;
    }
    
    // used only for HTML representation
    public function fetchSetsForTenant($code) {
        if (TENANTS_AND_SETS_IN_MYSQL) {
            $response = $this->fetchMySQLSetsForCode($code);
            $retVal = $this->arrangeMySqlSets($response);
            return $retVal;
        } else {
            $query = 'SELECT ?seturi ?p ?o WHERE  { ?tenanturi  <' . OpenSkos::CODE . "> '" . $code . "' ."
                    . ' ?seturi  <' . DcTerms::PUBLISHER . '> ?tenantUri .'
                    . ' ?seturi  ?p ?o .}';
            $response = $this->query($query);
            if ($response !== null) {
                if (count($response) > 0) {
                    $retVal = $this->arrangeTripleStoreSets($response);
                    return $retVal;
                } 
            }
        }
        return [];
    }

    // used only for html output
    private function arrangeTripleStoreSets($response) {
        $retVal = [];
        foreach ($response as $triple) {
            $seturi = $triple -> seturi->getUri();
            if (!array_key_exists($seturi, $retVal)) {
                $retVal[$seturi]=[];
            };
            switch ($triple -> p) {
                case DcTerms::TITLE:
                    $retVal[$seturi]['dcterms_title']=$triple->o->getValue();
                    continue;
                case DcTerms::DESCRIPTION:
                    $retVal[$seturi]['dcterms_description']=$triple->o->getValue();
                    continue;
                case OpenSkos::WEBPAGE:
                    $retVal[$seturi]['openskos_webpage']=$triple->o->getUri();
                    continue;
                case OpenSkos::CODE:
                    $retVal[$seturi]['openskos_code']=$triple->o->getValue();
                    continue; 
                case OpenSkos::UUID:
                    $retVal[$seturi]['openskos_uuid']=$triple->o->getValue();
                    continue;
                default: continue;
            }
        }
        return $retVal;
        
    }
    
    // used only for html output
    private function arrangeMySQLSets($mysqlresponse) {
        $retVal = [];
        foreach ($mysqlresponse as $row) {
            if (isset($row['code'])) {
                $id = $row['code'];
            } else {
                throw new ApiException("A set with no code in MySQL databse is detected", 400);
            }

            $retVal[$id] = [];

            $retVal[$id]['dcterms_title'] = $row['dc_title'];
            if (isset($row['dc_decription'])) {
                $retVal[$id]['dcterms_description'] = $row['dc_decription'];
            }
            if (isset($row['website'])) {
                $retVal[$id]['openskos_webpage'] = $row['website'];
            }
            $retVal[$id]['openskos_code'] = $row['code'];
            $retVal[$id]['openskos_uuid'] = $row['code'];
        }
        return $retVal;
    }

    private function fetchMySQLSetsForCode($tenantCode){
        $model = new OpenSKOS_Db_Table_Tenants();
        $tenant = $model->find($tenantCode)->current();
        if ($tenant===null) {
           throw new ApiException("Tenant with the code '". $tenantCode . "' is not found in MySQL", 400);
        }
        $sets = $tenant->findDependentRowset('OpenSKOS_Db_Table_Collections');
        return $sets;
    }
    
    public function translateTenantMySqlToRdf($tenantMySQL) {
        $tenantResource = new Tenant();
        if (!isset($tenantMySQL['uri'])) {
            $tenantResource->setUri('http://unset_uri_in_mysqldatabase');
        } else {
            $tenantResource->setUri($tenantMySQL['uri']);
        }
        $tenantResource->setProperty(OpenSkos::CODE, new \OpenSkos2\Rdf\Literal($tenantMySQL['code']));
        $organisation = new Resource("node-org");
        $this->setLiteralWithEmptinessCheck($organisation, vCard::ORGNAME, $tenantMySQL['name']);
        $this->setLiteralWithEmptinessCheck($organisation, vCard::ORGUNIT, $tenantMySQL['organisationUnit']);
        $tenantResource->setProperty(vCard::ORG, $organisation);
        $this->setUriWithEmptinessCheck($tenantResource, OpenSkos::WEBPAGE, $tenantMySQL['website']);
        $this->setLiteralWithEmptinessCheck($tenantResource, vCard::EMAIL, $tenantMySQL['email']);

        $adress = new Resource("node-adr");
        $this->setLiteralWithEmptinessCheck($adress, vCard::STREET, $tenantMySQL['streetAddress']);
        $this->setLiteralWithEmptinessCheck($adress, vCard::LOCALITY, $tenantMySQL['locality']);
        $this->setLiteralWithEmptinessCheck($adress, vCard::PCODE, $tenantMySQL['postalCode']);
        $this->setLiteralWithEmptinessCheck($adress, vCard::COUNTRY, $tenantMySQL['countryName']);
        $tenantResource->setProperty(vCard::ADR, $adress);
        
        $this->setBooleanLiteralWithEmptinessCheck($tenantResource, OpenSkos::DISABLESEARCHINOTERTENANTS, $tenantMySQL['disableSearchInOtherTenants']);
        if (array_key_exists('enableStatussesSystem', $tenantMySQL)){
          $this->setBooleanLiteralWithEmptinessCheck($tenantResource, OpenSkos::ENABLESTATUSSESSYSTEMS, $tenantMySQL['enableStatussesSystem']);
        } else {
            $this->setBooleanLiteralWithEmptinessCheck($tenantResource, OpenSkos::ENABLESTATUSSESSYSTEMS, ENABLE_STATUSSES_SYSTEM);
        }

        return $tenantResource;
    }

    
    public function fetchFromMySQL($params) {
        $model = new OpenSKOS_Db_Table_Tenants();
        $select = $model->select();
        $mysqlres = $model->fetchAll($select);
        $index = new TenantCollection();
        foreach ($mysqlres as $tenant) {
            $rdfTenant = $this->translateTenantMySqlToRdf($tenant);
            $index->append($rdfTenant);
        }
        return $index;
    }

    
}
