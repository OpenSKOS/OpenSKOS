<?php

namespace OpenSkos2\Api;

use OpenSkos2\Api\AbstractTripleStoreResource;
use OpenSkos2\Api\Exception\ApiException;
use OpenSkos2\Namespaces\vCard;
use OpenSkos2\Namespaces\Org;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\TenantManager;
use OpenSkos2\TenantCollection;
use OpenSkos2\MyInstitutionModules\Authorisation;
use OpenSkos2\MyInstitutionModules\Deletion;
use OpenSkos2\Rdf\Resource;
use OpenSkos2\Tenant as RdfTenant;
use OpenSKOS_Db_Table_Tenants;

class Tenant extends AbstractTripleStoreResource
{
    public function __construct(TenantManager $manager) {
        $this->manager = $manager;
        $this->authorisationManager = new Authorisation($manager);
        $this->deletionManager = new Deletion($manager);
    }
    
    
     // specific content validation
    protected function validate($resourceObject, $tenant) {
       parent::validate($resourceObject, $tenant);
       $name = $this -> getInstitutionName($resourceObject);
       $insts= $this -> manager -> fetchSubjectWithPropertyGiven(vCard::ORGNAME, '"'.$name.'"');
       if (count($insts)>0) {
           throw new ApiException('The institution with the name ' . $name . ' has been already registered.', 400);
       }
       $this->validatePropertyForCreate($resourceObject, OpenSkos::CODE, Org::FORMALORG);
    }
    
     // specific content validation
    protected function validateForUpdate($resourceObject, $tenant,  $existingResourceObject) {
        parent::validateForUpdate($resourceObject, $tenant, $existingResourceObject);
        
        // check the  name and the code (if they are new)
        $name = $this->getInstitutionName($resourceObject);
        $oldName = $this->getInstitutionName($existingResourceObject);
        if ($name !== $oldName) {
            // new name should not occur amnogst existing institution names
            $insts = $this->manager->fetchSubjectWithPropertyGiven(vCard::ORGNAME, '"'.$name.'"');
            if (count($insts) > 0) {
                throw new ApiException('The institution with the name ' . $name . ' has been already registered.', 400);
            }
        }
        $this->validatePropertyForUpdate($resourceObject, $existingResourceObject, OpenSkos::CODE, Org::FORMALORG);
    }
    
    private function getInstitutionName($inst) {
       $org = $inst->getProperty(vCard::ORG);
       $name= $org[0] -> getProperty(vCard::ORGNAME);
       return trim($name[0]); 
    }
    
    protected function fetchFromMySQL() {
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

    private function translateTenantMySqlToRdf($tenantMySQL) {
        $tenantResource = new RdfTenant();
        if (!isset($tenantMySQL['uri'])) {
            $tenantResource->setUri('http://unset_uri_in_mysqldatabase');
        } else {
            $tenantResource->setUri($tenantMySQL['uri']);
        }
        $tenantResource->setProperty(OpenSkos::CODE, new \OpenSkos2\Rdf\Literal($tenantMySQL['code']));
        $organisation = new Resource("node-org");
        $this->manager->setLiteralWithEmptinessCheck($organisation, vCard::ORGNAME, $tenantMySQL['name']);
        $this->manager->setLiteralWithEmptinessCheck($organisation, vCard::ORGUNIT, $tenantMySQL['organisationUnit']);
        $tenantResource->setProperty(vCard::ORG, $organisation);
        $this->manager->setUriWithEmptinessCheck($tenantResource, OpenSkos::WEBPAGE, $tenantMySQL['website']);
        $this->manager->setLiteralWithEmptinessCheck($tenantResource, vCard::EMAIL, $tenantMySQL['email']);

        $adress = new Resource("node-adr");
        $this->manager->setLiteralWithEmptinessCheck($adress, vCard::STREET, $tenantMySQL['streetAddress']);
        $this->manager->setLiteralWithEmptinessCheck($adress, vCard::LOCALITY, $tenantMySQL['locality']);
        $this->manager->setLiteralWithEmptinessCheck($adress, vCard::PCODE, $tenantMySQL['postalCode']);
        $this->manager->setLiteralWithEmptinessCheck($adress, vCard::COUNTRY, $tenantMySQL['countryName']);
        $tenantResource->setProperty(vCard::ADR, $adress);
        
        $this->manager->setBooleanLiteralWithEmptinessCheck($tenantResource, OpenSkos::DISABLESEARCHINOTERTENANTS, $tenantMySQL['disableSearchInOtherTenants']);
        if (array_key_exists('enableStatussesSystem', $tenantMySQL)){
          $this->manager->setBooleanLiteralWithEmptinessCheck($tenantResource, OpenSkos::ENABLESTATUSSESSYSTEMS, $tenantMySQL['enableStatussesSystem']);
        } else {
            $this->manager->setBooleanLiteralWithEmptinessCheck($tenantResource, OpenSkos::ENABLESTATUSSESSYSTEMS, ENABLE_STATUSSES_SYSTEM);
        }

        return $tenantResource;
    }

}
