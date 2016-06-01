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
use OpenSkos2\Tenant;
use OpenSKOS_Db_Table_Tenants;
use OpenSkos2\Api\Exception\ApiException;

class TenantManager extends ResourceManager
{
  
    protected $resourceType = Tenant::TYPE;
   
    public function fetchUriName() {
        $query = 'SELECT ?uri ?name WHERE { ?uri  <' . vCard::ORG . '> ?org . ?org <' . vCard::ORGNAME . '> ?name . }';
        $response = $this->query($query);
        $result = $this->makeJsonUriNameMap($response);
        return $result;
    }
    
    // used only for HTML representation
    public function fetchSetsForTenant($reference) {
        $response = null;
        $retVal = [];
        if ($reference instanceof Uri) {
            $tenantUri = $reference->getUri();
            $query = 'SELECT ?seturi ?p ?o WHERE  {  ?seturi  <' . DcTerms::PUBLISHER . '> <' . $tenantUri . '> .'
                    . ' ?seturi  ?p ?o .}';
            $retVal = $this->arrangeTripleStoreSets($response);
            return $retVal;
        } else { // must be a code, a literal
            $tenantCode = $reference->getValue();
            $query = 'SELECT ?seturi ?p ?o WHERE  { ?tenanturi  <' . OpenSkos::CODE . "> '" . $tenantCode . "' ."
                    . ' ?seturi  <' . DcTerms::PUBLISHER . '> ?tenantUri .'
                    . ' ?seturi  ?p ?o .}';
            $response = $this->query($query);
            if ($response !== null) {
                if (count($response) > 0) {
                    $retVal = $this->arrangeTripleStoreSets($response);
                    return $retVal;
                } else { // check mysql 
                    $retVal = $this->checkMySQLSets($tenantCode);
                    return $retVal;
                }
            } else {
                $retVal = $this->checkMySQLSets($tenantCode);
                return $retVal;
            }
        }
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
    
    // used only for htmll output
    private function arrangeMySQLSets($mysqlresponse) {
        $retVal = [];
        foreach ($mysqlresponse as $row) {
            if (isset($row['uuid'])) {
                $id = $row['uuid'];
            } else {
                if (isset($row['uri'])) {
                    $id = $row['id'];
                } else {
                    if (isset($row['id'])) {
                        $id = $row['id'];
                    } else {
                throw new ApiException("A set with no uuid in MySQL databse is detected", 400);
                    }
                }
            }
            
            if (!array_key_exists($id, $retVal)) {
                $retVal[$id]=[];
            };
            $retVal[$id]['dcterms_title'] = $row['dc_title'];
            if (isset($row['dc_decription'])) {
               $retVal[$id]['dcterms_description'] = $row['dc_decription'];
            }
            if (isset($row['website'])) {
                $retVal[$id]['openskos_webpage'] = $row['website'];
            }
            $retVal[$id]['openskos_code'] = $row['code'];
            if (isset($row['uuid'])) {
                $retVal[$id]['openskos_uuid'] = $row['uuid'];
            }
        } 
        return $retVal;
    }
    
    private function checkMySQLSets($tenantCode){
        $model = new OpenSKOS_Db_Table_Tenants();
        $tenant = $model->find($tenantCode)->current();
        if ($tenant===null) {
           throw new ApiException("Tenant with the code '". $tenantCode . "' is not found in MySQL", 400);
        }
        $sets = $tenant->findDependentRowset('OpenSKOS_Db_Table_Collections');
        $retVal = $this->arrangeMySQLSets($sets);
        return $retVal;
    }
    
}
