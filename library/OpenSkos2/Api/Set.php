<?php



namespace OpenSkos2\Api;

use OpenSkos2\Namespaces\Dcmi;
use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Namespaces\Org;
use OpenSkos2\SetManager;
use OpenSkos2\SetCollection;
use OpenSkos2\MyInstitutionModules\Authorisation;
use OpenSkos2\MyInstitutionModules\Deletion;
use OpenSKOS_Db_Table_Collections;

class Set extends AbstractTripleStoreResource
{
    public function __construct(SetManager $manager) {
        $this->manager = $manager;
        $this->authorisationManager = new Authorisation($manager);
        $this->deletionManager = new Deletion($manager);
    }
    
    
    // specific content validation
     protected function validate($resourceObject, $tenant) {
       parent::validate($resourceObject, $tenant);
       $this->validatePropertyForCreate($resourceObject, DcTerms::TITLE, Dcmi::DATASET);
       $this->validatePropertyForCreate($resourceObject, OpenSkos::CODE, Dcmi::DATASET);
       $this->validateURI($resourceObject, DcTerms::PUBLISHER,Org::FORMALORG);
    }
    
    
     // specific content validation
    protected function validateForUpdate($resourceObject, $tenant,  $existingResourceObject) {
        parent::validateForUpdate($resourceObject, $tenant, $existingResourceObject);
        // check the  titles and the code (if they are new)
        $this->validatePropertyForUpdate($resourceObject, $existingResourceObject, DcTerms::TITLE, Dcmi::DATASET);
        $this->validatePropertyForUpdate($resourceObject, $existingResourceObject, OpenSkos::CODE, Dcmi::DATASET);
        
        $this->validateURI($resourceObject, DcTerms::PUBLISHER,Org::FORMALORG);
    }
    
    protected function fetchFromMySQL($params) {
        $model = new OpenSKOS_Db_Table_Collections();
        $select = $model->select();
        if ($params['allow_oai']==='true') {
                    $select->where('allow_oai=?', 'Y');
        };
        if ($params['allow_oai']==='false') {
                    $select->where('allow_oai=?', 'N');
        };
        $mysqlres = $model->fetchAll($select);
        $index = new SetCollection();
        foreach ($mysqlres as $collection) {
            $rdfSet = $this->translateCollectionMySqlToRdfSet($collection);
            $index->append($rdfSet);
        }
        return $index;
    }

    private function translateCollectionMySqlToRdfSet($collectionMySQL) {
        $setResource = new \OpenSkos2\Set();
        if (!isset($collectionMySQL['uri'])) {
            $setResource->setUri('http://unset_uri_in_mysqldatabase');
        } else {
            $setResource->setUri($collectionMySQL['uri']);
        }
        
        $this->manager->setLiteralWithEmptinessCheck($setResource, OpenSkos::CODE, $collectionMySQL['code']);
        $this->manager->setLiteralWithEmptinessCheck($setResource, DcTerms::PUBLISHER, $collectionMySQL['tenant']);
        $this->manager->setLiteralWithEmptinessCheck($setResource, DcTerms::TITLE, $collectionMySQL['dc_title']);
        $this->manager->setUriWithEmptinessCheck($setResource, OpenSkos::WEBPAGE, $collectionMySQL['website']);
        $this->manager->setUriWithEmptinessCheck($setResource, DcTerms::LICENSE, $collectionMySQL['license_url']);
        $this->manager->setUriWithEmptinessCheck($setResource, OpenSkos::OAI_BASEURL, $collectionMySQL['OAI_baseURL']);
        $this->manager->setBooleanLiteralWithEmptinessCheck($setResource, OpenSkos::ALLOW_OAI, $collectionMySQL['allow_oai']);
        $this->manager->setUriWithEmptinessCheck($setResource, OpenSkos::CONCEPTBASEURI, $collectionMySQL['conceptsBaseUrl']);
        return $setResource;
    }

}
